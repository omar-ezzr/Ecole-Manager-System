// tailwind.component.config.js
module.exports = {
  content: [
    // Paths to your component’s Blade and JS files
    './resources/views/livewire/table-eleve.blade.php',
    './app/Http/Livewire/TableEleve.php',
  ],
  // Add a unique prefix/selector to scope styles
  important: '#component-container', // 🔑 Scopes all styles to this container
  theme: {
    extend: {},
  },
  plugins: [],
}