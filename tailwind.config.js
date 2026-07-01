module.exports = {
  content: [
    './*.php',
    './inc/**/*.php',
    './template-parts/**/*.php',
    './assets/js/**/*.js'
  ],
  theme: {
    extend: {
      colors: {
        ink: '#08234a',
        navy: '#071f42',
        gold: '#f59a3c',
        mist: '#f4f7fb',
        line: '#e4ebf4'
      },
      borderRadius: {
        lg: 'var(--radius)',
        md: 'calc(var(--radius) - 2px)',
        sm: 'calc(var(--radius) - 4px)',
        '4xl': '2rem'
      },
      boxShadow: {
        soft: '0 18px 50px rgba(8, 35, 74, 0.09)',
        card: '0 12px 34px oklch(1 0 0)'
      }
    }
  },
  plugins: []
};
