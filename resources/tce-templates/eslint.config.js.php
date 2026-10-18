// @ts-check
import tseslint from 'typescript-eslint';

/**
 * The mechanically enforced part of "Coding Style": explicit accessibility on every class
 * member, and no raw external input outside the SDK's validation helpers.
 */
const VALIDATE = 'External input must be parsed through a zod schema — see "Coding Style: Validate External Input".';

export default tseslint.config(
    {
        ignores: ['dist/**', 'vendor/**', 'release/**'],
    },
    {
        files: ['src/**/*.ts'],
        extends: [...tseslint.configs.recommended],
        rules: {
            '@typescript-eslint/explicit-member-accessibility': ['error', {
                accessibility: 'explicit',
            }],
            'no-restricted-properties': ['error',
                { object: 'JSON', property: 'parse', message: `${VALIDATE} Use parse_json() or read_storage_json().` },
                { object: 'Number', property: 'parseInt', message: `${VALIDATE} Use read_attribute() with z.coerce.number().` },
                { object: 'Number', property: 'parseFloat', message: `${VALIDATE} Use read_attribute() with z.coerce.number().` },
            ],
            'no-restricted-globals': ['error',
                { name: 'parseInt', message: `${VALIDATE} Use read_attribute() with z.coerce.number().` },
                { name: 'parseFloat', message: `${VALIDATE} Use read_attribute() with z.coerce.number().` },
            ],
            'no-restricted-syntax': ['error',
                {
                    selector: "CallExpression[callee.property.name='json'][arguments.length=0]",
                    message: `${VALIDATE} Use fetch_json(url, schema).`,
                },
                {
                    selector: "TSAsExpression > TSTypeReference[typeName.name='CustomEvent']",
                    message: `${VALIDATE} Use event_detail(event, schema).`,
                },
                {
                    selector: "MemberExpression[property.name='getItem']:matches([object.name='localStorage'], [object.property.name='localStorage'])",
                    message: `${VALIDATE} Use read_storage_json(), or read_storage_text() for plain text.`,
                },
            ],
        },
    },
);
