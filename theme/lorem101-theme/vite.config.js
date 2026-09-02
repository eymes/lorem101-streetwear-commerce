import { defineConfig } from "vite";
import { resolve } from "path";

export default defineConfig({
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
		origin: "http://localhost:5173",
	},
});
