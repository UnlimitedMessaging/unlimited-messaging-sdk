"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.UnlimitedMessagingApi = void 0;
class UnlimitedMessagingApi {
    constructor() {
        this.name = 'unlimitedMessagingApi';
        this.displayName = 'Unlimited Messaging API';
        this.documentationUrl = 'https://docs.unlimitedmessaging.app';
        this.icon = 'file:unlimitedmessaging.svg';
        this.properties = [
            {
                displayName: 'API Token',
                name: 'apiToken',
                type: 'string',
                typeOptions: { password: true },
                default: '',
                hint: 'Generate your token at https://app.unlimitedmessaging.app/settings/tokens',
            },
        ];
        // Injects "Authorization: Bearer <token>" on every request automatically
        this.authenticate = {
            type: 'generic',
            properties: {
                headers: {
                    Authorization: '=Bearer {{$credentials.apiToken}}',
                },
            },
        };
        // n8n uses this to let users test their credential in the UI
        this.test = {
            request: {
                baseURL: 'https://api.unlimitedmessaging.app',
                url: '/sim',
                method: 'GET',
            },
        };
    }
}
exports.UnlimitedMessagingApi = UnlimitedMessagingApi;
