import BaseApi from './BaseApi';

export class SeriesApi extends BaseApi {
    /** The names of the series you own — the only ones you may file a session under. */
    static async index(): Promise<string[]> {
        const response = await BaseApi.httpRequest.get<string[]>(`/series`);
        return response.data;
    }

    /**
     * File one growth session under a series, or take it out of one by passing null. Separate from
     * the update endpoint, which is closed on sessions that have already happened.
     */
    static async file(growthSessionId: number, seriesName: string | null): Promise<string | null> {
        const response = await BaseApi.httpRequest.put<{ series_name: string | null }>(`/growth_sessions/${growthSessionId}/series`, {
            series_name: seriesName,
        });
        return response.data.series_name;
    }
}
