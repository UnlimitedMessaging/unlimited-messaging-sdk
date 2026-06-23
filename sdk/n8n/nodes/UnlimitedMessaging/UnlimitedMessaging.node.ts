import type {
	IDataObject,
	IExecuteFunctions,
	IHttpRequestOptions,
	INodeExecutionData,
	INodeType,
	INodeTypeDescription,
	IAllExecuteFunctions,
} from 'n8n-workflow';
import { NodeConnectionTypes, NodeOperationError, NodeApiError } from 'n8n-workflow';
import type { JsonObject } from 'n8n-workflow';

const BASE_URL = 'https://api.unlimitedmessaging.app';

// httpRequestWithAuthentication requires `this: IAllExecuteFunctions` (union type in n8n-workflow 2.x).
// IExecuteFunctions is a member of that union, so we cast for the .call() invocation.
function request(
	ctx: IExecuteFunctions,
	credentialName: string,
	options: IHttpRequestOptions,
): Promise<IDataObject> {
	return ctx.helpers.httpRequestWithAuthentication.call(
		ctx as unknown as IAllExecuteFunctions,
		credentialName,
		options,
	);
}

export class UnlimitedMessaging implements INodeType {
	description: INodeTypeDescription = {
		displayName: 'Unlimited Messaging',
		name: 'unlimitedMessaging',
		icon: 'file:unlimitedmessaging.svg',
		group: ['transform'],
		version: 1,
		subtitle: '={{$parameter["operation"] + " " + $parameter["resource"]}}',
		description: 'Send and receive WhatsApp messages via the Unlimited Messaging API',
		defaults: { name: 'Unlimited Messaging' },
		usableAsTool: true,
		inputs: [NodeConnectionTypes.Main],
		outputs: [NodeConnectionTypes.Main],
		credentials: [
			{
				name: 'unlimitedMessagingApi',
				required: true,
			},
		],
		properties: [
			// ─── Resource ──────────────────────────────────────────────────────────
			{
				displayName: 'Resource',
				name: 'resource',
				type: 'options',
				noDataExpression: true,
				options: [
					{ name: 'Message', value: 'message' },
					{ name: 'SIM', value: 'sim' },
				],
				default: 'message',
			},

			// ─── Message operations ─────────────────────────────────────────────
			{
				displayName: 'Operation',
				name: 'operation',
				type: 'options',
				noDataExpression: true,
				displayOptions: { show: { resource: ['message'] } },
				options: [
					{
						name: 'Get',
						value: 'get',
						description: 'Get a message by ID',
						action: 'Get a message',
					},
					{
						name: 'List',
						value: 'list',
						description: 'List messages with optional filters',
						action: 'List messages',
					},
					{
						name: 'Send',
						value: 'send',
						description: 'Send a WhatsApp message',
						action: 'Send a message',
					},
				],
				default: 'send',
			},

			// ─── SIM operations ─────────────────────────────────────────────────
			{
				displayName: 'Operation',
				name: 'operation',
				type: 'options',
				noDataExpression: true,
				displayOptions: { show: { resource: ['sim'] } },
				options: [
					{
						name: 'List',
						value: 'list',
						description: 'List all linked SIM cards (WhatsApp accounts)',
						action: 'List SIMs',
					},
				],
				default: 'list',
			},

			// ─── Message > Send ─────────────────────────────────────────────────
			{
				displayName: 'Recipient',
				name: 'recipient',
				type: 'string',
				required: true,
				default: '',
				placeholder: '+33612345678',
				description: 'Phone number of the recipient in E.164 format',
				displayOptions: { show: { resource: ['message'], operation: ['send'] } },
			},
			{
				displayName: 'Text',
				name: 'text',
				type: 'string',
				required: true,
				default: '',
				typeOptions: { rows: 3 },
				description: 'Message content (max 1 600 characters)',
				displayOptions: { show: { resource: ['message'], operation: ['send'] } },
			},
			{
				displayName: 'SIM ID',
				name: 'simId',
				type: 'string',
				default: '',
				description:
					'ID of the SIM card (WhatsApp account) to send from. Leave empty to let Unlimited Messaging pick one automatically.',
				displayOptions: { show: { resource: ['message'], operation: ['send'] } },
			},

			// ─── Message > Get ──────────────────────────────────────────────────
			{
				displayName: 'Message ID',
				name: 'messageId',
				type: 'string',
				required: true,
				default: '',
				description: 'ID of the message to retrieve',
				displayOptions: { show: { resource: ['message'], operation: ['get'] } },
			},

			// ─── Message > List ─────────────────────────────────────────────────
			{
				displayName: 'Return All',
				name: 'returnAll',
				type: 'boolean',
				default: false,
				description:
					'Whether to return all results by paginating automatically, or only the first page',
				displayOptions: { show: { resource: ['message'], operation: ['list'] } },
			},
			{
				displayName: 'Limit',
				name: 'limit',
				type: 'number',
				default: 20,
				typeOptions: { minValue: 1, maxValue: 100 },
				description: 'Max number of messages to return',
				displayOptions: {
					show: { resource: ['message'], operation: ['list'], returnAll: [false] },
				},
			},
			{
				displayName: 'Filters',
				name: 'filters',
				type: 'collection',
				placeholder: 'Add Filter',
				default: {},
				displayOptions: { show: { resource: ['message'], operation: ['list'] } },
				options: [
					{
						displayName: 'Status',
						name: 'status',
						type: 'options',
						options: [
							{ name: 'Delivered', value: 'DELIVERED' },
							{ name: 'Failed', value: 'FAILED' },
							{ name: 'Pending', value: 'PENDING' },
							{ name: 'Read', value: 'READ' },
							{ name: 'Sending', value: 'SENDING' },
							{ name: 'Sent', value: 'SENT' },
						],
						default: 'SENT',
						description: 'Filter messages by delivery status',
					},
					{
						displayName: 'SIM ID',
						name: 'simId',
						type: 'string',
						default: '',
						description: 'Filter messages from a specific SIM card',
					},
					{
						displayName: 'Search',
						name: 'search',
						type: 'string',
						default: '',
						description: 'Full-text search in message content',
					},
				],
			},
		],
	};

	async execute(this: IExecuteFunctions): Promise<INodeExecutionData[][]> {
		const items = this.getInputData();
		const results: INodeExecutionData[] = [];

		for (let i = 0; i < items.length; i++) {
			try {
				const resource = this.getNodeParameter('resource', i) as string;
				const operation = this.getNodeParameter('operation', i) as string;
				let responseData: IDataObject | IDataObject[];

				if (resource === 'message') {
					if (operation === 'send') {
						const recipient = this.getNodeParameter('recipient', i) as string;
						const text = this.getNodeParameter('text', i) as string;
						const simId = this.getNodeParameter('simId', i) as string;

						const body: IDataObject = { recipient, text };
						if (simId) body.simId = simId;

						responseData = await request(this, 'unlimitedMessagingApi', {
							method: 'POST',
							url: `${BASE_URL}/message`,
							body,
							json: true,
						});
					} else if (operation === 'get') {
						const messageId = this.getNodeParameter('messageId', i) as string;

						responseData = await request(this, 'unlimitedMessagingApi', {
							method: 'GET',
							url: `${BASE_URL}/message/${messageId}`,
							json: true,
						});
					} else if (operation === 'list') {
						const returnAll = this.getNodeParameter('returnAll', i) as boolean;
						const filters = this.getNodeParameter('filters', i) as IDataObject;

						// Strip empty strings so they don't appear in the query string
						const cleanFilters = Object.fromEntries(
							Object.entries(filters).filter(([, v]) => v !== '' && v !== null && v !== undefined),
						);

						if (returnAll) {
							const all: IDataObject[] = [];
							let page = 1;
							let totalPages = 1;

							do {
								const resp = await request(this, 'unlimitedMessagingApi', {
									method: 'GET',
									url: `${BASE_URL}/message`,
									qs: { page, limit: 100, ...cleanFilters },
									json: true,
								});
								all.push(...(resp.data as IDataObject[]));
								totalPages = resp.totalPages as number;
								page++;
							} while (page <= totalPages);

							responseData = all;
						} else {
							const limit = this.getNodeParameter('limit', i) as number;
							const resp = await request(this, 'unlimitedMessagingApi', {
								method: 'GET',
								url: `${BASE_URL}/message`,
								qs: { page: 1, limit, ...cleanFilters },
								json: true,
							});
							responseData = resp.data as IDataObject[];
						}
					} else {
						throw new NodeOperationError(this.getNode(), `Unknown operation: ${operation}`, { itemIndex: i });
					}
				} else if (resource === 'sim') {
					if (operation === 'list') {
						responseData = (await request(this, 'unlimitedMessagingApi', {
							method: 'GET',
							url: `${BASE_URL}/sim`,
							json: true,
						})) as unknown as IDataObject[];
					} else {
						throw new NodeOperationError(this.getNode(), `Unknown operation: ${operation}`, { itemIndex: i });
					}
				} else {
					throw new NodeOperationError(this.getNode(), `Unknown resource: ${resource}`, { itemIndex: i });
				}

				const executionData = this.helpers.constructExecutionMetaData(
					this.helpers.returnJsonArray(
						Array.isArray(responseData) ? responseData : [responseData],
					),
					{ itemData: { item: i } },
				);

				results.push(...executionData);
			} catch (error) {
				if (this.continueOnFail()) {
					results.push({ json: { error: (error as Error).message }, pairedItem: { item: i } });
					continue;
				}
				throw new NodeApiError(this.getNode(), error as JsonObject, { itemIndex: i });
			}
		}

		return [results];
	}
}
