import type {
	IAuthenticateGeneric,
	ICredentialTestRequest,
	ICredentialType,
	INodeProperties,
} from 'n8n-workflow';

export class UnlimitedMessagingApi implements ICredentialType {
	name = 'unlimitedMessagingApi';
	displayName = 'Unlimited Messaging API';
	documentationUrl = 'https://docs.unlimitedmessaging.app';

	properties: INodeProperties[] = [
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
	authenticate: IAuthenticateGeneric = {
		type: 'generic',
		properties: {
			headers: {
				Authorization: '=Bearer {{$credentials.apiToken}}',
			},
		},
	};

	// n8n uses this to let users test their credential in the UI
	test: ICredentialTestRequest = {
		request: {
			baseURL: 'https://api.unlimitedmessaging.app',
			url: '/sim',
			method: 'GET',
		},
	};
}
