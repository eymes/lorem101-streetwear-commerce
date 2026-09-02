import "../scss/main.scss";

/**
 * Punkt wejścia JS motywu. Vite dzięki temu importowi w JS wrzuca
 * skompilowany CSS do tego samego chunku - stąd w manifest.json
 * plik JS ma przypisane pole "css".
 */

document.addEventListener("DOMContentLoaded", () => {
	// WooCommerce triggeruje customowy event po zaktualizowaniu koszyka przez AJAX
	document.body.addEventListener("added_to_cart", (event) => {
		console.log("Produkt dodany do koszyka", event);
	});

	initEntranceGate();
	initCategoryFilter();
	initVariationPreselectFromUrl();
	initFooterYear();
	initHideColorRow();
	initSizeValidation();
	initDust();
	initVariationAvailability();
});

/**
 * Prezentacja dostępności wariantów.
 *
 * Trzy rzeczy naraz, bo wszystkie zależą od tych samych danych:
 *   1. wyprzedane rozmiary w liście rozwijanej dostają czerwone przekreślenie,
 *   2. komunikat o dostępności ląduje pod przyciskiem zakupu,
 *   3. przy wyprzedanym wariancie pole ilości ustępuje miejsca napisowi.
 *
 * WooCommerce umieszcza pełne dane wszystkich wariantów w atrybucie
 * data-product_variations formularza, więc nie musimy o nic pytać serwera.
 */
function initVariationAvailability() {
	const form = document.querySelector(".variations_form");
	if (!form) {
		return;
	}

	let variations = [];

	try {
		variations = JSON.parse(form.dataset.product_variations || "[]");
	} catch (error) {
		return;
	}

	if (!Array.isArray(variations) || !variations.length) {
		return;
	}

	markSoldOutSizes(form, variations);
	watchStockState(form, variations);
}

/**
 * Oznacza w liście rozmiarów te warianty, których nie ma w magazynie.
 *
 * Dostępność sprawdzamy w obrębie aktualnej kolorystyki - ten sam rozmiar
 * bywa dostępny w czerni, a wyprzedany w bieli.
 */
function markSoldOutSizes(form, variations) {
	const sizeSelect = form.querySelector(
		'select[name^="attribute_"]:not([name="attribute_kolor"])'
	);
	const colorSelect = form.querySelector('select[name="attribute_kolor"]');

	if (!sizeSelect) {
		return;
	}

	const sizeAttribute = sizeSelect.name;
	const currentColor = colorSelect ? colorSelect.value : "";

	Array.from(sizeSelect.options).forEach((option) => {
		if (!option.value) {
			return; // pozycja "Choose an option"
		}

		const match = variations.find((variation) => {
			const attributes = variation.attributes || {};

			if (attributes[sizeAttribute] !== option.value) {
				return false;
			}

			// Pusty atrybut koloru w danych oznacza "dowolny"
			if (!currentColor || !attributes.attribute_kolor) {
				return true;
			}

			return attributes.attribute_kolor === currentColor;
		});

		option.classList.toggle("is-sold-out", Boolean(match && !match.is_in_stock));
	});
}

/**
 * Komunikat o dostępności pod przyciskiem zakupu.
 *
 * Nie przenosimy elementu WooCommerce, tylko tworzymy własny i wypełniamy go
 * danymi wariantu. Powód: WooCommerce przy każdej zmianie wariantu renderuje
 * swój komunikat od nowa w pierwotnym miejscu - przeniesiony egzemplarz
 * zostawał wtedy jako nieaktualna kopia i widać było dwa napisy naraz.
 *
 * Oryginalny komunikat chowamy w CSS (.woocommerce-variation-availability).
 */
function watchStockState(form, variations) {
	if (!window.jQuery) {
		return;
	}

	const $form = window.jQuery(form);
	const quantity = form.querySelector(".quantity");
	const cartWrapper = form.querySelector(".woocommerce-variation-add-to-cart");

	if (!cartWrapper) {
		return;
	}

	const stockInfo = document.createElement("p");
	stockInfo.className = "variation-stock";
	stockInfo.hidden = true;
	cartWrapper.appendChild(stockInfo);

	$form.on("found_variation", (event, variation) => {
		const inStock = Boolean(variation.is_in_stock);

		// Pole ilości nie ma sensu, gdy nie ma czego kupić
		if (quantity) {
			quantity.hidden = !inStock;
		}

		if (inStock) {
			// availability_html to gotowy fragment od WooCommerce.
			// Powyżej progu niskiego stanu WooCommerce wypisuje samo
			// "In stock" - bez liczby ta informacja nic nie wnosi, więc
			// pokazujemy komunikat tylko wtedy, gdy zawiera cyfrę
			// (np. "Only 15 left in stock").
			const html = variation.availability_html || "";
			const hasNumber = /\d/.test(html);

			stockInfo.innerHTML = hasNumber ? html : "";
			stockInfo.hidden = !hasNumber;
		} else {
			// Przy wyprzedanym wariancie nie piszemy nic - formularz
			// "powiadom mnie" pojawia się poniżej i mówi wszystko
			stockInfo.hidden = true;
			stockInfo.innerHTML = "";
		}

		markSoldOutSizes(form, variations);
	});

	$form.on("reset_data", () => {
		if (quantity) {
			quantity.hidden = false;
		}

		stockInfo.hidden = true;
		stockInfo.innerHTML = "";
	});
}

/**
 * Unoszący się pył w tle strony głównej.
 *
 * Rysujemy na canvasie zamiast tworzyć setki elementów DOM - przeglądarka
 * przelicza wtedy jedną warstwę zamiast animować każdą cząstkę osobno.
 *
 * Szanujemy ustawienie "ogranicz animacje" z systemu operacyjnego
 * (prefers-reduced-motion): dla osób wrażliwych na ruch animacja się
 * nie uruchamia.
 */
// Uchwyty zatrzymujące animację pyłu - initEntranceGate przerywa je
// w momencie kliknięcia "Enter" (patrz stopDust() niżej).
let dustStopFns = [];

function stopDust() {
	dustStopFns.forEach((stop) => stop());
	dustStopFns = [];
}

function initDust() {
	const canvases = document.querySelectorAll("[data-dust]");

	if (!canvases.length) {
		return;
	}

	if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
		return;
	}

	// Canvas dekoruje wyłącznie bramkę wejściową. Jeśli sesja już ją pominęła
	// (sessionStorage z initEntranceGate), bramka jest od razu ukryta i pętla
	// animacji rysowałaby na niewidocznym elemencie w nieskończoność, marnując
	// czas procesora przy każdej kolejnej wizycie - dlatego w ogóle jej nie startujemy.
	if (sessionStorage.getItem("lorem101_entered") === "1") {
		return;
	}

	canvases.forEach((canvas) => {
		dustStopFns.push(startDustAnimation(canvas));
	});

	// Zabezpieczenie na wypadek braku interakcji (np. zautomatyzowany audyt
	// wydajności, który nigdy nie klika "Enter") - dekoracyjna animacja nie
	// ma powodu działać w nieskończoność, jeśli nikt na nią nie patrzy.
	window.setTimeout(stopDust, 8000);
}

function startDustAnimation(canvas) {
	const context = canvas.getContext("2d");

	if (!context) {
		return () => {};
	}

	// Na ekranach o dużej gęstości pikseli rysujemy w wyższej rozdzielczości,
	// inaczej cząstki wyglądałyby na rozmyte
	const ratio = window.devicePixelRatio || 1;

	let width = 0;
	let height = 0;
	let particles = [];

	function resize() {
		width = canvas.offsetWidth;
		height = canvas.offsetHeight;

		canvas.width = width * ratio;
		canvas.height = height * ratio;
		context.scale(ratio, ratio);

		// Liczba cząstek zależy od powierzchni - na małym ekranie mniej,
		// żeby efekt zachował podobną gęstość. Górny limit obniżony ze 140
		// do 90 po pomiarze wydajności na słabszym procesorze - efekt
		// wizualnie prawie identyczny, koszt każdej klatki wyraźnie niższy.
		const count = Math.min(90, Math.round((width * height) / 9000));

		particles = [];

		for (let i = 0; i < count; i += 1) {
			particles.push(createParticle());
		}
	}

	function createParticle() {
		return {
			x: Math.random() * width,
			y: Math.random() * height,
			radius: Math.random() * 1.4 + 0.4,
			// lekki dryf w bok i powolne opadanie
			driftX: (Math.random() - 0.5) * 0.18,
			speedY: Math.random() * 0.28 + 0.06,
			opacity: Math.random() * 0.35 + 0.08,
			// przesunięcie fazy, żeby cząstki nie migotały zgodnie
			phase: Math.random() * Math.PI * 2,
		};
	}

	function draw() {
		context.clearRect(0, 0, width, height);

		particles.forEach((particle) => {
			particle.y += particle.speedY;
			particle.x += particle.driftX + Math.sin(particle.phase) * 0.12;
			particle.phase += 0.008;

            // Cząstka, która wyszła poza dolną krawędź, wraca na górę -
            // dzięki temu strumień nigdy się nie kończy
			if (particle.y > height + 5) {
				particle.y = -5;
				particle.x = Math.random() * width;
			}

			if (particle.x < -5) {
				particle.x = width + 5;
			} else if (particle.x > width + 5) {
				particle.x = -5;
			}

			context.beginPath();
			context.arc(particle.x, particle.y, particle.radius, 0, Math.PI * 2);
			context.fillStyle = "rgba(245, 243, 238, " + particle.opacity + ")";
			context.fill();
		});

		frameId = window.requestAnimationFrame(draw);
	}

	let frameId = null;
	resize();
	draw();

	// Przy zmianie rozmiaru okna przeliczamy wymiary canvasu i liczbę cząstek
	let resizeTimer = null;

	const onResize = () => {
		window.clearTimeout(resizeTimer);
		resizeTimer = window.setTimeout(resize, 200);
	};

	window.addEventListener("resize", onResize);

	// Uchwyt zatrzymujący: przerywa pętlę rysowania i odpina nasłuch resize,
	// żeby po zatrzymaniu animacja nie zostawiła żadnego działającego kodu.
	return () => {
		if (frameId !== null) {
			window.cancelAnimationFrame(frameId);
		}
		window.removeEventListener("resize", onResize);
	};
}

/**
 * Walidacja wyboru rozmiaru i dodawanie do koszyka bez przeładowania.
 *
 * WooCommerce dla produktów zmiennych wysyła formularz klasycznie, co
 * przeładowuje stronę i gubi wybraną kolorystykę. Przechwytujemy wysłanie
 * i robimy to przez własny endpoint REST (patrz inc/rest-cart.php).
 *
 * Walidacja rozmiaru to warstwa UX - serwer i tak sprawdza kompletność
 * wariantu, więc pominięcie JS niczego nie omija.
 */
function initSizeValidation() {
	const form = document.querySelector(".variations_form");
	if (!form) {
		return;
	}

	const button = form.querySelector(".single_add_to_cart_button");
	// Kolor jest ukryty i ustawiany z adresu URL, więc walidujemy pozostałe
	// atrybuty wariantu - w praktyce rozmiar.
	const sizeSelect = form.querySelector(
		'select[name^="attribute_"]:not([name="attribute_kolor"])'
	);

	if (!button || !sizeSelect) {
		return;
	}

	const message = document.createElement("p");
	message.className = "size-error";
	message.setAttribute("role", "alert");
	message.hidden = true;
	button.insertAdjacentElement("afterend", message);

	const showError = (text) => {
		message.textContent = text;
		message.hidden = false;
		message.classList.remove("size-error--success");
	};

	const showSuccess = (text) => {
		message.textContent = text;
		message.hidden = false;
		message.classList.add("size-error--success");
	};

	const clearMessage = () => {
		message.hidden = true;
		sizeSelect.classList.remove("has-error");
	};

	const unlockButton = () => {
		button.removeAttribute("disabled");
		button.classList.remove("disabled", "wc-variation-selection-needed");
	};

	// Zdejmujemy blokadę przy starcie i po każdej zmianie rozmiaru.
	// Świadomie BEZ MutationObserver - obserwowanie atrybutu, który sami
	// zmieniamy, tworzy nieskończoną pętlę i zawiesza stronę.
	unlockButton();

	sizeSelect.addEventListener("change", () => {
		unlockButton();

		if (sizeSelect.value) {
			clearMessage();
		}
	});

	form.addEventListener("submit", (event) => {
		event.preventDefault();

		if (!sizeSelect.value) {
			sizeSelect.classList.add("has-error");
			showError("Wybierz rozmiar, aby dodać do koszyka.");
			return;
		}

		submitAddToCart(form, button, { showError, showSuccess });
	});
}

/**
 * Wysyła dane wariantu do naszego endpointu i aktualizuje interfejs.
 */
function submitAddToCart(form, button, ui) {
	const config = window.lorem101Rest;

	if (!config || !config.addToCart) {
		// Brak konfiguracji oznacza, że coś poszło nie tak po stronie PHP.
		// Puszczamy formularz klasycznie, żeby zakup był nadal możliwy.
		form.submit();
		return;
	}

	// WooCommerce renderuje formularz z atrybutem data-product_id
	// (z podkreślnikiem), więc w dataset trafia jako product_id, a nie
	// productId. Ukryte pole add-to-cart jest zapasowym źródłem.
	const productId = parseInt(
		form.dataset.product_id || form.querySelector('[name="add-to-cart"]')?.value || 0,
		10
	);
	const variationId = parseInt(form.querySelector('[name="variation_id"]')?.value || 0, 10);
	const quantityField = form.querySelector('[name="quantity"]');
	const quantity = parseInt(quantityField?.value || 1, 10);

	// Zbieramy wszystkie atrybuty wariantu, także ukryty kolor
	const attributes = {};

	form.querySelectorAll('[name^="attribute_"]').forEach((field) => {
		attributes[field.name] = field.value;
	});

	const originalLabel = button.textContent;
	button.disabled = true;
	button.textContent = "Dodawanie…";

	fetch(config.addToCart, {
		method: "POST",
		headers: {
			"Content-Type": "application/json",
			"X-WP-Nonce": config.nonce,
		},
		// Bez tego przeglądarka nie wyśle ciasteczka sesji i serwer
		// dopisałby produkt do innego (anonimowego) koszyka
		credentials: "same-origin",
		body: JSON.stringify({
			product_id: productId,
			variation_id: variationId,
			quantity: quantity,
			attributes: attributes,
		}),
	})
		.then((response) => response.json().then((data) => ({ ok: response.ok, data })))
		.then(({ ok, data }) => {
			if (!ok) {
				ui.showError(data.message || "Nie udało się dodać produktu do koszyka.");
				return;
			}

			updateCartCount(data.cart_count);
			ui.showSuccess("Dodano do koszyka.");
		})
		.catch(() => {
			ui.showError("Błąd połączenia. Spróbuj ponownie.");
		})
		.finally(() => {
			button.disabled = false;
			button.textContent = originalLabel;
		});
}

/**
 * Aktualizuje licznik na ikonie koszyka w nagłówku.
 *
 * Licznik istnieje w HTML tylko wtedy, gdy koszyk nie jest pusty (PHP go
 * wtedy nie renderuje), więc przy pierwszym dodaniu produktu trzeba go
 * utworzyć.
 */
function updateCartCount(count) {
	const cart = document.querySelector(".site-header__cart");

	if (!cart || typeof count !== "number") {
		return;
	}

	let counter = cart.querySelector(".site-header__cart-count");

	if (count <= 0) {
		if (counter) {
			counter.remove();
		}
		return;
	}

	if (!counter) {
		counter = document.createElement("span");
		counter.className = "site-header__cart-count";
		cart.appendChild(counter);
	}

	counter.textContent = count;
}

/**
 * Chowa wiersz "Kolor" z formularza wariantu na stronie produktu.
 * Kolor wybiera się przez kliknięcie kafelka w sekcji "Inne kolory"
 * (każdy prowadzi na ten sam produkt z innym ?attribute_kolor), a PHP
 * wymusza zaznaczenie właściwej opcji w ukrytym polu - patrz filtr
 * woocommerce_dropdown_variation_attribute_options_args.
 *
 * Robimy to w JS, a nie CSS, bo WooCommerce nie nadaje wierszom tabeli
 * wariantów klas rozróżniających atrybut - selektor CSS musiałby polegać
 * na kolejności wierszy i psułby się przy każdej zmianie atrybutów.
 */
function initHideColorRow() {
	const colorSelect = document.querySelector('.variations select[name="attribute_kolor"]');
	if (!colorSelect) {
		return;
	}

	const row = colorSelect.closest("tr");
	if (row) {
		row.classList.add("is-hidden");
	}
}

/**
 * Dynamiczna data w stopce - podmienia rok wyrenderowany przez PHP
 * na aktualny rok z zegara przeglądarki.
 */
function initFooterYear() {
	const yearSpan = document.getElementById("current-year");
	if (yearSpan) {
		yearSpan.textContent = new Date().getFullYear();
	}
}

/**
 * Entrance gate: pokazujemy pełnoekranowy ekran powitalny tylko RAZ na sesję
 * przeglądarki (sessionStorage - znika po zamknięciu karty, w przeciwieństwie
 * do localStorage). Jeśli odwiedzający już raz kliknął ENTER w tej sesji,
 * przy kolejnych wejściach na stronę główną gate jest pomijany bez animacji.
 *
 * Fade/loading działa na prostym setTimeout, bez biblioteki animacji.
 */
function initEntranceGate() {
	const gate = document.getElementById("entrance-gate");
	if (!gate) {
		return; // nie ma gate na tej stronie (np. nie jesteśmy na stronie głównej)
	}

	const STORAGE_KEY = "lorem101_entered";
	const button = document.getElementById("entrance-gate-button");

	// Już wchodziłeś w tej sesji - pomiń gate od razu, bez animacji
	if (sessionStorage.getItem(STORAGE_KEY) === "1") {
		gate.classList.add("is-hidden");
		return;
	}

	document.body.classList.add("entrance-gate-active");

	button.addEventListener("click", () => {
		gate.classList.add("is-loading");

		// Pył jest czysto dekoracyjny i istnieje tylko po to, żeby ożywić
		// bramkę - w momencie jej opuszczenia nie ma już czego rysować.
		stopDust();

		window.setTimeout(() => {
			gate.classList.remove("is-loading");
			gate.classList.add("is-hidden");
			document.body.classList.remove("entrance-gate-active");
			sessionStorage.setItem(STORAGE_KEY, "1");
		}, 1200);
	});
}

/**
 * Filtr kategorii na stronie głównej (All / Hoodies / T-shirts / Shorts).
 * Czysto front-endowe filtrowanie po klasach nadawanych przez WooCommerce
 * (product_cat-{slug} na każdym <li class="product">) - bez przeładowania
 * strony i bez zapytań AJAX, bo katalog jest na razie mały. Przy większym
 * katalogu warto by to zamienić na WC_Query + AJAX, ale tutaj byłoby
 * nadmiarowe.
 */
function initCategoryFilter() {
	const tabs = document.querySelectorAll(".category-tabs__item");
	const grid = document.querySelector("#shop-grid ul.products");
	const productItems = document.querySelectorAll("#shop-grid ul.products > li");

	if (!tabs.length || !productItems.length) {
		return;
	}

	tabs.forEach((tab) => {
		tab.addEventListener("click", () => {
			const filter = tab.dataset.filter;

			tabs.forEach((t) => {
				t.classList.remove("is-active");
				t.setAttribute("aria-selected", "false");
			});
			tab.classList.add("is-active");
			tab.setAttribute("aria-selected", "true");

			// Widok ALL pokazuje pięć kolumn, pojedyncza kategoria cztery
			// (patrz reguła .is-filtered w woocommerce/_shop.scss)
			if (grid) {
				grid.classList.toggle("is-filtered", filter !== "all");
			}

			productItems.forEach((item) => {
				const matches = filter === "all" || item.classList.contains("product_cat-" + filter);
				item.classList.toggle("is-hidden", !matches);
			});
		});
	});
}

/**
 * Kliknięcie kafelka koloru na stronie głównej prowadzi na stronę produktu
 * z parametrem ?attribute_kolor={slug}. Tu odczytujemy ten parametr i
 * zaznaczamy odpowiadający <select name="attribute_kolor"> w formularzu
 * wariantu WooCommerce, wysyłając zdarzenie "change" żeby WooCommerce
 * przeliczyło dostępne rozmiary/cenę tak jakby klient wybrał kolor ręcznie.
 */
function initVariationPreselectFromUrl() {
	const params = new URLSearchParams(window.location.search);
	const colorSlug = params.get("attribute_kolor");
	if (!colorSlug) {
		return;
	}

	const select = document.querySelector('select[name="attribute_kolor"]');
	if (!select) {
		return;
	}

	const optionExists = Array.from(select.options).some((opt) => opt.value === colorSlug);
	if (!optionExists) {
		return;
	}

	select.value = colorSlug;
	select.dispatchEvent(new Event("change", { bubbles: true }));
}
