import plugin from '@n8n/eslint-plugin-community-nodes';
import tsParser from '@typescript-eslint/parser';

export default [
	{
		...plugin.configs.recommended,
		files: ['**/*.ts'],
		languageOptions: {
			parser: tsParser,
		},
	},
	{
		files: ['package.json'],
		...plugin.configs.recommended,
	},
];
