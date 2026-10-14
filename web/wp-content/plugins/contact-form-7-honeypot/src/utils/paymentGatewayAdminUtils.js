import { useCallback, useEffect, useRef, useState } from "@wordpress/element";

export const resolveEnvironmentNoticeOnModeChange = (
	nextMode,
	previousMode,
	{ connectedTest }
) => {
	if ( nextMode === "live" && previousMode === "test" && connectedTest ) {
		return "live";
	}

	if ( nextMode === "test" && previousMode === "live" && connectedTest ) {
		return "test";
	}

	return null;
};

export const resolveEnvironmentNoticeAfterAuth = ( mode, { connectedTest, connectedLive } ) => {
	if ( mode === "test" && connectedTest ) {
		return "test";
	}

	if ( mode === "live" && connectedLive && connectedTest ) {
		return "live";
	}

	return null;
};

export const usePaymentGatewayEnvironmentNotice = ( connection, savedEnvironmentMode ) => {
	const [ pendingNotice, setPendingNotice ] = useState( null );
	const previousModeRef = useRef( null );
	const hasRestoredNoticeRef = useRef( false );
	const connectedTest = !! connection?.connected_test;

	useEffect( () => {
		if ( connection === null || ! savedEnvironmentMode || hasRestoredNoticeRef.current ) {
			return;
		}

		previousModeRef.current = savedEnvironmentMode;
		hasRestoredNoticeRef.current = true;

		if ( savedEnvironmentMode === "test" && connectedTest ) {
			setPendingNotice( "test" );
		}
	}, [ savedEnvironmentMode, connection, connectedTest ] );

	useEffect( () => {
		if ( pendingNotice === "live" && ! connectedTest ) {
			setPendingNotice( null );
		}

		if ( pendingNotice === "test" && ! connectedTest ) {
			setPendingNotice( null );
		}
	}, [ connectedTest, pendingNotice ] );

	const applyModeSelection = useCallback( ( nextMode, setMode ) => {
		const previousMode = previousModeRef.current || savedEnvironmentMode;
		setMode( nextMode );
		previousModeRef.current = nextMode;

		setPendingNotice(
			resolveEnvironmentNoticeOnModeChange( nextMode, previousMode, {
				connectedTest,
			} )
		);
	}, [ connectedTest, savedEnvironmentMode ] );

	const showNoticeAfterAuth = useCallback( ( mode, connectionSnapshot = connection ) => {
		setPendingNotice(
			resolveEnvironmentNoticeAfterAuth( mode, {
				connectedTest: !! connectionSnapshot?.connected_test,
				connectedLive: !! connectionSnapshot?.connected_live,
			} )
		);
	}, [ connection ] );

	return {
		showTestNotice: pendingNotice === "test",
		showLiveNotice: pendingNotice === "live",
		applyModeSelection,
		showNoticeAfterAuth,
	};
};

export const shouldShowPaymentGatewayConnectButton = ( {
	connectedNow,
	isEnabled,
	savedIsEnabled,
} ) => {
	if ( connectedNow ) {
		return false;
	}

	return !! isEnabled || !! savedIsEnabled;
};

export const hasUnsavedPaymentGatewayEnableChange = ( isEnabled, savedIsEnabled ) => {
	return isEnabled !== savedIsEnabled;
};

export const canUsePaymentGatewayConnect = ( {
	savedIsEnabled,
	hasUnsavedEnableChange,
	isSaving = false,
} ) => {
	return !! savedIsEnabled && ! hasUnsavedEnableChange && ! isSaving;
};
