import { defineConfig } from "vite";
import { resolve } from "path";

export default defineConfig({
	// Motyw jest serwowany spod /wp-content/themes/lorem101-theme/dist/,
	// więc ścieżki w zbudowanych plikach muszą być względne
	base: "",
	build: {
		outDir: "dist",
		manifest: true,
		rollupOptions: {
			input: resolve(__dirname, "src/js/main.js"),
		},
	},
	server: {
		port: 5173,
		strictPort: true,
		cors: true,
		// Pozwala WordPressowi (innej domenie/portowi) pobierać pliki w trybie dev
		origin: "http://localhost:5173",
	},
});
