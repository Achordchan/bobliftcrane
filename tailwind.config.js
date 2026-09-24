module.exports = {
  content: [
    './*.php',
    './inc/**/*.php',
    './template-parts/**/*.php',
    './assets/js/**/*.js'
  ],
  theme: {
    // Industrial look: 2px for controls, 4px for cards and panels, circles only for dots.
    // Every named size maps onto that scale so stray classes cannot drift back to pills.
    borderRadius: {
      none: '0',
      sm: '2px',
      DEFAULT: '4px',
      md: '4px',
      lg: '4px',
      xl: '4px',
      '2xl': '4px',
      '3xl': '4px',
      full: '9999px'
    },
    // Flat surfaces separated by borders; only floating layers (menus, dock, dialogs) get real elevation.
    boxShadow: {
      none: 'none',
      sm: '0 1px 2px rgba(8, 35, 74, 0.06)',
      DEFAULT: '0 1px 2px rgba(8, 35, 74, 0.06)',
      card: '0 1px 2px rgba(8, 35, 74, 0.06)',
      soft: '0 1px 2px rgba(8, 35, 74, 0.06)',
      md: '0 8px 24px rgba(8, 35, 74, 0.12)',
      lg: '0 8px 24px rgba(8, 35, 74, 0.12)',
      xl: '0 8px 24px rgba(8, 35, 74, 0.12)',
      '2xl': '0 8px 24px rgba(8, 35, 74, 0.12)',
      raised: '0 8px 24px rgba(8, 35, 74, 0.12)',
      inner: 'inset 0 2px 4px 0 rgba(8, 35, 74, 0.05)'
    },
    extend: {
      fontFamily: {
        sans: ['Barlow', '"Helvetica Neue"', 'Arial', 'sans-serif']
      },
      colors: {
        ink: '#08234a',
        navy: '#071f42',
        gold: '#f59a3c',
        mist: '#f4f7fb',
        line: '#e4ebf4'
      }
    }
  },
  plugins: []
};
