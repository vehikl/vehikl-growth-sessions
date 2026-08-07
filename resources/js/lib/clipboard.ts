/**
 * `navigator.clipboard` is missing entirely outside a secure context, which is every
 * plain-http development host, so the deprecated selection copy is the fallback rather
 * than an immediate failure.
 */
export async function copyToClipboard(text: string): Promise<boolean> {
    try {
        if (navigator.clipboard) {
            await navigator.clipboard.writeText(text);
            return true;
        }
    } catch (error) {
        console.error('Could not copy to the clipboard:', error);
    }

    return copyBySelection(text);
}

function copyBySelection(text: string): boolean {
    const field = document.createElement('textarea');
    field.value = text;
    field.setAttribute('readonly', '');
    field.style.position = 'fixed';
    field.style.top = '0';
    field.style.opacity = '0';
    document.body.appendChild(field);

    try {
        field.select();

        return document.execCommand('copy');
    } catch (error) {
        console.error('Could not copy by selection:', error);
        return false;
    } finally {
        field.remove();
    }
}
