/**
 * @see https://prettier.io/docs/en/configuration.html
 * @type {import("prettier").Config}
 */
const config = {
  singleQuote: true,
  tabWidth: 2,
  trailingComma: 'none',
  plugins: ['@prettier/plugin-xml'],
  overrides: [
    {
      files: '*.xml',
      options: {
        printWidth: 120,
        tabWidth: 4,
        xmlQuoteAttributes: 'double',
        xmlWhitespaceSensitivity: 'ignore'
      }
    }
  ]
};

export default config;
