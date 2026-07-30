import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import path from 'path'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  server: {
    port: 3000,
    open: true,
  },
  // Ensure Three.js and shader packages are not externalized
  optimizeDeps: {
    include: [
      '@shadergradient/react',
      '@react-three/fiber',
      'three',
      'three-stdlib',
      'camera-controls',
    ],
  },
})
