import BaseApi from './BaseApi';

export class SeriesApi extends BaseApi {
    /**
     * The series you are running, for the picker in the growth session form. Only your own, since
     * a series belongs to whoever started it and nobody else may file a session under it.
     */
    static async index(): Promise<string[]> {
        const response = await BaseApi.httpRequest.get<string[]>(`/series`);
        return response.data;
    }

    /**
     * File one growth session under a series, or take it out of one by passing null.
     *
     * Its own endpoint rather than an update: a session that has already happened cannot be edited,
     * but the owner may still say afterwards which thread it belonged to. A name they do not
     * already run starts a series of their own, whoever else may use the same words.
     */
    static async file(growthSessionId: number, seriesName: string | null): Promise<string | null> {
        const response = await BaseApi.httpRequest.put<{ series_name: string | null }>(`/growth_sessions/${growthSessionId}/series`, {
            series_name: seriesName,
        });
        return response.data.series_name;
    }
}
