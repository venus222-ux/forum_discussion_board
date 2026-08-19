import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import { visualizer } from "rollup-plugin-visualizer";
import path from "path";

export default defineConfig({
  plugins: [
    react(),
    visualizer({
      open: true,
      gzipSize: true,
      brotliSize: true,
    }),
  ],

  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./src"),
    },
  },

  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          react: ["react", "react-dom"],
          zustand: ["zustand"],
          vendor: ["axios"],
        },
      },
    },
  },

 server: {
  host: true, // ensures Vite listens on 0.0.0.0 inside the container, not just its own localhost
  proxy: {
    "/api": {
      target: "http://laravel_app:8000",
      changeOrigin: true,
    },
  },
},
});
