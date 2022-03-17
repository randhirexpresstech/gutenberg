/**
 * External dependencies
 */
import { KeyboardAwareScrollView } from 'react-native-keyboard-aware-scroll-view';
import { FlatList } from 'react-native';
import { isEqual } from 'lodash';
import Animated, {
	useAnimatedScrollHandler,
	useSharedValue,
} from 'react-native-reanimated';

/**
 * WordPress dependencies
 */
import { memo } from '@wordpress/element';

const List = memo( FlatList, isEqual );
const AnimatedKeyboardAwareScrollView = Animated.createAnimatedComponent(
	KeyboardAwareScrollView
);

export const KeyboardAwareFlatList = ( {
	extraScrollHeight,
	shouldPreventAutomaticScroll,
	innerRef,
	autoScroll,
	scrollViewStyle,
	inputAccessoryViewHeight,
	onScroll,
	...listProps
} ) => {
	const latestContentOffsetY = useSharedValue( -1 );
	const scrollHandler = useAnimatedScrollHandler( {
		onScroll: ( event ) => {
			const { contentOffset } = event;
			latestContentOffsetY.value = contentOffset.y;
			onScroll( event );
		},
	} );
	return (
		<AnimatedKeyboardAwareScrollView
			style={ [ { flex: 1 }, scrollViewStyle ] }
			keyboardDismissMode="none"
			enableResetScrollToCoords={ false }
			keyboardShouldPersistTaps="handled"
			extraScrollHeight={ extraScrollHeight }
			extraHeight={ 0 }
			inputAccessoryViewHeight={ inputAccessoryViewHeight }
			enableAutomaticScroll={
				autoScroll === undefined ? false : autoScroll
			}
			ref={ ( ref ) => {
				this.scrollViewRef = ref;
				innerRef( ref );
			} }
			onKeyboardWillHide={ () => {
				this.keyboardWillShowIndicator = false;
			} }
			onKeyboardDidHide={ () => {
				setTimeout( () => {
					if (
						! this.keyboardWillShowIndicator &&
						latestContentOffsetY.value !== -1 &&
						! shouldPreventAutomaticScroll()
					) {
						// Reset the content position if keyboard is still closed.
						if ( this.scrollViewRef ) {
							this.scrollViewRef.scrollToPosition(
								0,
								latestContentOffsetY.value,
								true
							);
						}
					}
				}, 50 );
			} }
			onKeyboardWillShow={ () => {
				this.keyboardWillShowIndicator = true;
			} }
			scrollEnabled={ listProps.scrollEnabled }
			onScroll={ scrollHandler }
		>
			<List { ...listProps } />
		</AnimatedKeyboardAwareScrollView>
	);
};

KeyboardAwareFlatList.handleCaretVerticalPositionChange = (
	scrollView,
	targetId,
	caretY,
	previousCaretY
) => {
	if ( previousCaretY ) {
		// If this is not the first tap.
		scrollView.refreshScrollForField( targetId );
	}
};

export default KeyboardAwareFlatList;
