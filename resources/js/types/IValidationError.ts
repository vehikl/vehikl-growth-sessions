export interface IValidationError {
    errors: {
        [field: string] : string[]
    },
    message: string;
}
