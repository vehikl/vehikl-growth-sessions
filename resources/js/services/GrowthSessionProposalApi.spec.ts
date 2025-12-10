import axios from 'axios';
import MockAdapter from 'axios-mock-adapter';
import { GrowthSessionProposalApi, proposalResource } from './GrowthSessionProposalApi';
import { IGrowthSessionProposal, IStoreGrowthSessionProposalRequest } from '@/types';

const mockProposal: IGrowthSessionProposal = {
    id: 1,
    title: 'Learn Laravel 12',
    topic: 'I want to learn the new features in Laravel 12',
    status: 'pending',
    creator: {
        id: 1,
        name: 'John Doe',
        avatar: 'avatar.jpg',
        github_nickname: 'johndoe',
        is_vehikl_member: false,
    },
    time_preferences: [
        { id: 1, weekday: 'Monday', start_time: '14:00', end_time: '17:00' },
        { id: 2, weekday: 'Wednesday', start_time: '10:00', end_time: '12:00' },
    ],
    tags: [
        { id: 1, name: 'Laravel' },
        { id: 2, name: 'PHP' },
    ],
    created_at: '2025-01-01T00:00:00.000000Z',
    updated_at: '2025-01-01T00:00:00.000000Z',
};

const mockProposals: IGrowthSessionProposal[] = [mockProposal];

describe('GrowthSessionProposalApi', () => {
    let mockBackend: MockAdapter;

    beforeEach(() => {
        mockBackend = new MockAdapter(axios);
    });

    afterEach(() => {
        mockBackend.restore();
    });

    it('fetches all proposals', async () => {
        mockBackend.onGet(proposalResource).reply(200, mockProposals);

        const result = await GrowthSessionProposalApi.index();

        expect(result).toEqual(mockProposals);
    });

    it('creates a new proposal', async () => {
        const payload: IStoreGrowthSessionProposalRequest = {
            title: 'Learn Laravel 12',
            topic: 'I want to learn the new features in Laravel 12',
            tags: [1, 2],
            time_preferences: [
                { weekday: 'Monday', start_time: '14:00', end_time: '17:00' },
            ],
        };

        mockBackend.onPost(proposalResource).reply(201, mockProposal);

        const result = await GrowthSessionProposalApi.store(payload);

        expect(result).toEqual(mockProposal);
        expect(mockBackend.history.post[0].url).toBe(`/${proposalResource}`);
    });

    it('updates an existing proposal', async () => {
        const proposalId = 1;
        const updatedProposal = { ...mockProposal, title: 'Updated Title' };

        mockBackend.onPut(`${proposalResource}/${proposalId}`).reply(200, updatedProposal);

        const result = await GrowthSessionProposalApi.update(proposalId, { title: 'Updated Title' });

        expect(result.title).toEqual('Updated Title');
    });

    it('deletes an existing proposal', async () => {
        const proposalId = 1;

        mockBackend.onDelete(`${proposalResource}/${proposalId}`).reply(204);

        const result = await GrowthSessionProposalApi.delete(proposalId);

        expect(result).toBe(true);
        expect(mockBackend.history.delete[0].url).toBe(`/${proposalResource}/${proposalId}`);
    });

    it('approves a proposal', async () => {
        const proposalId = 1;
        const approvalPayload = {
            title: mockProposal.title,
            topic: mockProposal.topic,
            location: 'Discord',
            date: '2025-11-03',
            start_time: '02:00 pm',
            end_time: '05:00 pm',
        };

        const approvedProposal = { ...mockProposal, status: 'approved' };
        mockBackend.onPost(`${proposalResource}/${proposalId}/approve`).reply(200, approvedProposal);

        const result = await GrowthSessionProposalApi.approve(proposalId, approvalPayload);

        expect(result.status).toBe('approved');
        expect(mockBackend.history.post[0].url).toBe(`/${proposalResource}/${proposalId}/approve`);
    });
});
