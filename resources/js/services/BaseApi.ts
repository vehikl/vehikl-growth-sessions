import axios, {AxiosStatic} from 'axios';

export default class BaseApi {
    static httpRequest: AxiosStatic = axios;
}
