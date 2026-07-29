/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './app/Views/**/*.php',
    './public/js/**/*.js',
  ],
  prefix: 'tw-',
  corePlugins: {
    preflight: false,
  },
  theme: {
    extend: {
      colors: {
        eams: {
          primary: '#3563d6',
          ink: '#172033',
          muted: '#64748b',
          border: '#dbe4f1',
          canvas: '#f3f6fc',
          sidebar: '#1C2434',
        },
      },
      boxShadow: {
        panel: '0 3px 12px rgba(15, 23, 42, 0.055)',
      },
    },
  },
  plugins: [],
};
