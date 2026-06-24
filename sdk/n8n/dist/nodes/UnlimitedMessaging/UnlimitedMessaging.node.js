"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.UnlimitedMessaging = void 0;
const n8n_workflow_1 = require("n8n-workflow");
const BASE_URL = 'https://api.unlimitedmessaging.app';
// httpRequestWithAuthentication requires `this: IAllExecuteFunctions` (union type in n8n-workflow 2.x).
// IExecuteFunctions is a member of that union, so we cast for the .call() invocation.
function request(ctx, credentialName, options) {
    return ctx.helpers.httpRequestWithAuthentication.call(ctx, credentialName, options);
}
class UnlimitedMessaging {
    constructor() {
        this.description = {
            displayName: 'Unlimited Messaging',
            name: 'unlimitedMessaging',
            icon: 'file:unlimitedmessaging.svg',
            group: ['transform'],
            version: 1,
            subtitle: '={{$parameter["operation"] + " " + $parameter["resource"]}}',
            description: 'Send and receive WhatsApp messages via the Unlimited Messaging API',
            defaults: { name: 'Unlimited Messaging' },
            usableAsTool: true,
            inputs: [n8n_workflow_1.NodeConnectionTypes.Main],
            outputs: [n8n_workflow_1.NodeConnectionTypes.Main],
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
                    description: 'ID of the SIM card (WhatsApp account) to send from. Leave empty to let Unlimited Messaging pick one automatically.',
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
                    description: 'Whether to return all results by paginating automatically, or only the first page',
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
    }
    async execute() {
        const items = this.getInputData();
        const results = [];
        for (let i = 0; i < items.length; i++) {
            try {
                const resource = this.getNodeParameter('resource', i);
                const operation = this.getNodeParameter('operation', i);
                let responseData;
                if (resource === 'message') {
                    if (operation === 'send') {
                        const recipient = this.getNodeParameter('recipient', i);
                        const text = this.getNodeParameter('text', i);
                        const simId = this.getNodeParameter('simId', i);
                        const body = { recipient, text };
                        if (simId)
                            body.simId = simId;
                        responseData = await request(this, 'unlimitedMessagingApi', {
                            method: 'POST',
                            url: `${BASE_URL}/message`,
                            body,
                            json: true,
                        });
                    }
                    else if (operation === 'get') {
                        const messageId = this.getNodeParameter('messageId', i);
                        responseData = await request(this, 'unlimitedMessagingApi', {
                            method: 'GET',
                            url: `${BASE_URL}/message/${messageId}`,
                            json: true,
                        });
                    }
                    else if (operation === 'list') {
                        const returnAll = this.getNodeParameter('returnAll', i);
                        const filters = this.getNodeParameter('filters', i);
                        // Strip empty strings so they don't appear in the query string
                        const cleanFilters = Object.fromEntries(Object.entries(filters).filter(([, v]) => v !== '' && v !== null && v !== undefined));
                        if (returnAll) {
                            const all = [];
                            let page = 1;
                            let totalPages = 1;
                            do {
                                const resp = await request(this, 'unlimitedMessagingApi', {
                                    method: 'GET',
                                    url: `${BASE_URL}/message`,
                                    qs: { page, limit: 100, ...cleanFilters },
                                    json: true,
                                });
                                all.push(...resp.data);
                                totalPages = resp.totalPages;
                                page++;
                            } while (page <= totalPages);
                            responseData = all;
                        }
                        else {
                            const limit = this.getNodeParameter('limit', i);
                            const resp = await request(this, 'unlimitedMessagingApi', {
                                method: 'GET',
                                url: `${BASE_URL}/message`,
                                qs: { page: 1, limit, ...cleanFilters },
                                json: true,
                            });
                            responseData = resp.data;
                        }
                    }
                    else {
                        throw new n8n_workflow_1.NodeOperationError(this.getNode(), `Unknown operation: ${operation}`, { itemIndex: i });
                    }
                }
                else if (resource === 'sim') {
                    if (operation === 'list') {
                        responseData = (await request(this, 'unlimitedMessagingApi', {
                            method: 'GET',
                            url: `${BASE_URL}/sim`,
                            json: true,
                        }));
                    }
                    else {
                        throw new n8n_workflow_1.NodeOperationError(this.getNode(), `Unknown operation: ${operation}`, { itemIndex: i });
                    }
                }
                else {
                    throw new n8n_workflow_1.NodeOperationError(this.getNode(), `Unknown resource: ${resource}`, { itemIndex: i });
                }
                const executionData = this.helpers.constructExecutionMetaData(this.helpers.returnJsonArray(Array.isArray(responseData) ? responseData : [responseData]), { itemData: { item: i } });
                results.push(...executionData);
            }
            catch (error) {
                if (this.continueOnFail()) {
                    results.push({ json: { error: error.message }, pairedItem: { item: i } });
                    continue;
                }
                throw new n8n_workflow_1.NodeApiError(this.getNode(), error, { itemIndex: i });
            }
        }
        return [results];
    }
}
exports.UnlimitedMessaging = UnlimitedMessaging;
