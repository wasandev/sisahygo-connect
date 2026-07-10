/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
    './app/Livewire/**/*.php',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Sarabun', 'Prompt', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
      colors: {
        brand: {
          blue: '#0D47FF',
          navy: '#0A183D',
          orange: '#FF7A00',
          orangeAccent: '#FFA64D',
          text: '#1A1F2C',
          muted: '#687280',
          light: '#F2F4F7',
        },
        connect: {
          primary: '#0D47FF',
          dark: '#0A183D',
          accent: '#FF7A00',
        },
      },
      boxShadow: {
        card: '0 12px 30px rgba(10, 24, 61, 0.08)',
      },
      borderRadius: {
        '2xl': '1rem',
      },
    },
  },
  plugins: [],
};
