export function getOAuthReturnStatus( paramName ) {
	const search = new URLSearchParams( window.location.search );
	if ( search.has( paramName ) ) {
		return search.get( paramName );
	}

	const hash = window.location.hash || '';
	const queryIndex = hash.indexOf( '?' );
	if ( queryIndex === -1 ) {
		return null;
	}

	const hashParams = new URLSearchParams( hash.substring( queryIndex + 1 ) );
	if ( hashParams.has( paramName ) ) {
		return hashParams.get( paramName );
	}

	return null;
}

export function clearOAuthReturnStatus( paramName ) {
	const search = new URLSearchParams( window.location.search );
	let changed = false;

	if ( search.has( paramName ) ) {
		search.delete( paramName );
		changed = true;
	}

	let hash = window.location.hash || '';
	const queryIndex = hash.indexOf( '?' );
	let hashPath = hash;
	let hashParams = new URLSearchParams();

	if ( queryIndex !== -1 ) {
		hashPath = hash.substring( 0, queryIndex );
		hashParams = new URLSearchParams( hash.substring( queryIndex + 1 ) );
		if ( hashParams.has( paramName ) ) {
			hashParams.delete( paramName );
			changed = true;
		}
	}

	if ( ! changed ) {
		return;
	}

	const hashQuery = hashParams.toString();
	const nextHash = hashQuery ? `${ hashPath }?${ hashQuery }` : hashPath;
	const nextSearch = search.toString();
	const url =
		window.location.pathname +
		( nextSearch ? `?${ nextSearch }` : '' ) +
		nextHash;

	window.history.replaceState( {}, '', url );
}
