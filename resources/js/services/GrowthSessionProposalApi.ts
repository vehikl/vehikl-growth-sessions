import BaseApi from './BaseApi';
import { IGrowthSessionProposal, IStoreGrowthSessionProposalRequest } from '@/types';

export const proposalResource = 'proposals';

export class GrowthSessionProposalApi extends BaseApi {
    static async index(): Promise<IGrowthSessionProposal[]> {
        let response = await BaseApi.httpRequest.get<IGrowthSessionProposal[]>(`/${proposalResource}`);
        return response.data;
    }

    static async store(payload: IStoreGrowthSessionProposalRequest): Promise<IGrowthSessionProposal> {
        let response = await BaseApi.httpRequest.post<IGrowthSessionProposal>(`/${proposalResource}`, payload);
        return response.data;
    }

    static async update(proposalId: number, payload: Partial<IStoreGrowthSessionProposalRequest>): Promise<IGrowthSessionProposal> {
        let response = await BaseApi.httpRequest.put<IGrowthSessionProposal>(`/${proposalResource}/${proposalId}`, payload);
        return response.data;
    }

    static async delete(proposalId: number): Promise<boolean> {
        await BaseApi.httpRequest.delete(`/${proposalResource}/${proposalId}`);
        return true;
    }

    static async approve(proposalId: number, payload: any): Promise<IGrowthSessionProposal> {
        let response = await BaseApi.httpRequest.post<IGrowthSessionProposal>(`/${proposalResource}/${proposalId}/approve`, payload);
        return response.data;
    }
}
