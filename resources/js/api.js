export async function getListings() {
    const response = await fetch(
        '/api/crypto/listings'
    );

    return await response.json();
}

export async function getGlobal() {
    const response = await fetch(
        '/api/crypto/global'
    );

    return await response.json();
}
