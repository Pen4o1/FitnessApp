import { CameraView, useCameraPermissions } from 'expo-camera';
import { SymbolView } from 'expo-symbols';
import { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Linking,
  Modal,
  Pressable,
  StyleSheet,
  useWindowDimensions,
  Vibration,
  View,
} from 'react-native';
import Animated, {
  Easing,
  useAnimatedStyle,
  useSharedValue,
  withRepeat,
  withTiming,
} from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { ThemedText } from '@/components/themed-text';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';
import { ApiError, scanFoodByBarcode } from '@/lib/api';
import type { FoodBarcodeScanResult, FoodSearchResult } from '@/types/nutrition';

type BarcodeScannerModalProps = {
  visible: boolean;
  onClose: () => void;
  onFoodFound: (food: FoodSearchResult, warnings: ScanWarnings) => void;
};

export type ScanWarnings = {
  hasAllergen: boolean;
  hasDietaryConflict: boolean;
};

type ScanPhase = 'scanning' | 'lookup' | 'error';

const VIEWFINDER_RATIO = 0.72;
const VIEWFINDER_MAX = 300;
const CORNER_SIZE = 28;
const CORNER_BORDER = 4;

export function BarcodeScannerModal({ visible, onClose, onFoodFound }: BarcodeScannerModalProps) {
  const theme = useTheme();
  const insets = useSafeAreaInsets();
  const { width: screenWidth, height: screenHeight } = useWindowDimensions();
  const [permission, requestPermission] = useCameraPermissions();

  const [phase, setPhase] = useState<ScanPhase>('scanning');
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [frozenBarcode, setFrozenBarcode] = useState<string | null>(null);

  const viewfinderSize = Math.min(screenWidth * VIEWFINDER_RATIO, VIEWFINDER_MAX, screenHeight * 0.42);
  const overlayTopHeight = (screenHeight - viewfinderSize) / 2;

  const scanLineProgress = useSharedValue(0);

  useEffect(() => {
    if (phase !== 'scanning' || !visible) {
      scanLineProgress.value = 0;
      return;
    }

    scanLineProgress.value = withRepeat(
      withTiming(1, { duration: 2200, easing: Easing.inOut(Easing.ease) }),
      -1,
      true,
    );
  }, [phase, visible, scanLineProgress]);

  const scanLineStyle = useAnimatedStyle(() => ({
    transform: [{ translateY: scanLineProgress.value * (viewfinderSize - 6) }],
  }));

  function handleClose() {
    if (phase === 'lookup') {
      return;
    }

    onClose();
  }

  function handleRetry() {
    setPhase('scanning');
    setErrorMessage(null);
    setFrozenBarcode(null);
  }

  async function handleBarcodeScanned(data: string) {
    if (phase !== 'scanning' || frozenBarcode !== null) {
      return;
    }

    Vibration.vibrate(100);
    setFrozenBarcode(data);
    setPhase('lookup');

    try {
      const result: FoodBarcodeScanResult = await scanFoodByBarcode(data);
      const { has_allergen, has_dietary_conflict, barcode: _barcode, ...food } = result;

      onFoodFound(food, {
        hasAllergen: has_allergen,
        hasDietaryConflict: has_dietary_conflict,
      });
      onClose();
    } catch (err) {
      if (err instanceof ApiError) {
        setErrorMessage(err.message);
      } else {
        setErrorMessage('Could not look up this barcode. Please try again.');
      }

      setPhase('error');
    }
  }

  function renderPermissionDenied() {
    return (
      <View style={[styles.permissionContainer, { paddingTop: insets.top + Spacing.four }]}>
        <View style={styles.permissionIconWrap}>
          <SymbolView
            name={{ ios: 'camera.fill', android: 'photo_camera', web: 'photo_camera' }}
            size={48}
            tintColor={theme.textSecondary}
          />
        </View>
        <ThemedText type="subtitle" style={styles.permissionTitle}>
          Camera access needed
        </ThemedText>
        <ThemedText themeColor="textSecondary" style={styles.permissionBody}>
          To scan barcodes on food packaging, allow camera access in your device settings.
        </ThemedText>
        <Pressable
          onPress={() => void Linking.openSettings()}
          style={({ pressed }) => [
            styles.settingsButton,
            { backgroundColor: theme.accent },
            pressed && styles.pressed,
          ]}>
          <ThemedText style={styles.settingsButtonText} type="smallBold">
            Open Settings
          </ThemedText>
        </Pressable>
        <Pressable onPress={handleClose} style={({ pressed }) => [styles.textButton, pressed && styles.pressed]}>
          <ThemedText themeColor="textSecondary" type="smallBold">
            Cancel
          </ThemedText>
        </Pressable>
      </View>
    );
  }

  function renderOverlay() {
    return (
      <View pointerEvents="none" style={StyleSheet.absoluteFill}>
        <View style={[styles.overlayPanel, { height: overlayTopHeight }]} />
        <View style={[styles.overlayRow, { height: viewfinderSize }]}>
          <View style={[styles.overlayPanel, styles.overlaySide]} />
          <View style={[styles.viewfinder, { width: viewfinderSize, height: viewfinderSize }]}>
            <View style={[styles.corner, styles.cornerTopLeft]} />
            <View style={[styles.corner, styles.cornerTopRight]} />
            <View style={[styles.corner, styles.cornerBottomLeft]} />
            <View style={[styles.corner, styles.cornerBottomRight]} />
            {phase === 'scanning' ? (
              <Animated.View style={[styles.scanLine, scanLineStyle, { backgroundColor: theme.accent }]} />
            ) : null}
          </View>
          <View style={[styles.overlayPanel, styles.overlaySide]} />
        </View>
        <View style={[styles.overlayPanel, styles.overlayBottom]} />
      </View>
    );
  }

  function renderContent() {
    if (!permission) {
      return (
        <View style={styles.centeredState}>
          <ActivityIndicator color={theme.accent} size="large" />
        </View>
      );
    }

    if (!permission.granted) {
      if (permission.canAskAgain) {
        return (
          <View style={[styles.permissionContainer, { paddingTop: insets.top + Spacing.four }]}>
            <View style={styles.permissionIconWrap}>
              <SymbolView
                name={{ ios: 'barcode.viewfinder', android: 'qr_code_scanner', web: 'qr_code_scanner' }}
                size={48}
                tintColor={theme.accent}
              />
            </View>
            <ThemedText type="subtitle" style={styles.permissionTitle}>
              Scan barcodes
            </ThemedText>
            <ThemedText themeColor="textSecondary" style={styles.permissionBody}>
              Point your camera at a product barcode to log food quickly.
            </ThemedText>
            <Pressable
              onPress={() => void requestPermission()}
              style={({ pressed }) => [
                styles.settingsButton,
                { backgroundColor: theme.accent },
                pressed && styles.pressed,
              ]}>
              <ThemedText style={styles.settingsButtonText} type="smallBold">
                Enable Camera
              </ThemedText>
            </Pressable>
            <Pressable onPress={handleClose} style={({ pressed }) => [styles.textButton, pressed && styles.pressed]}>
              <ThemedText themeColor="textSecondary" type="smallBold">
                Cancel
              </ThemedText>
            </Pressable>
          </View>
        );
      }

      return renderPermissionDenied();
    }

    return (
      <>
        <CameraView
          style={StyleSheet.absoluteFill}
          facing="back"
          onBarcodeScanned={phase === 'scanning' ? ({ data }) => void handleBarcodeScanned(data) : undefined}
          barcodeScannerSettings={{
            barcodeTypes: ['ean13', 'ean8', 'upc_a', 'upc_e'],
          }}
        />
        {renderOverlay()}

        <View style={[styles.header, { paddingTop: insets.top + Spacing.two }]}>
          <Pressable
            accessibilityLabel="Close scanner"
            onPress={handleClose}
            disabled={phase === 'lookup'}
            style={({ pressed }) => [
              styles.closeButton,
              { backgroundColor: 'rgba(0, 0, 0, 0.55)' },
              pressed && styles.pressed,
              phase === 'lookup' && styles.disabled,
            ]}>
            <SymbolView name={{ ios: 'xmark', android: 'close', web: 'close' }} size={18} tintColor="#FFFFFF" />
          </Pressable>
        </View>

        <View style={[styles.footer, { paddingBottom: insets.bottom + Spacing.four }]}>
          {phase === 'lookup' ? (
            <View style={styles.lookupState}>
              <ActivityIndicator color="#FFFFFF" />
              <ThemedText style={styles.footerText} type="smallBold">
                Looking up product…
              </ThemedText>
            </View>
          ) : phase === 'error' ? (
            <View style={styles.errorState}>
              <ThemedText style={styles.errorBannerText} type="smallBold">
                {errorMessage}
              </ThemedText>
              <Pressable
                onPress={handleRetry}
                style={({ pressed }) => [
                  styles.retryButton,
                  { backgroundColor: theme.accent },
                  pressed && styles.pressed,
                ]}>
                <ThemedText style={styles.settingsButtonText} type="smallBold">
                  Scan again
                </ThemedText>
              </Pressable>
            </View>
          ) : (
            <ThemedText style={styles.footerText} type="small">
              Align the barcode inside the frame
            </ThemedText>
          )}
        </View>
      </>
    );
  }

  return (
    <Modal animationType="slide" visible={visible} onRequestClose={handleClose}>
      <View style={styles.container}>{renderContent()}</View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#000000',
  },
  centeredState: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  permissionContainer: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: Spacing.five,
    gap: Spacing.three,
    backgroundColor: '#000000',
  },
  permissionIconWrap: {
    width: 88,
    height: 88,
    borderRadius: 44,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(255, 255, 255, 0.08)',
    marginBottom: Spacing.two,
  },
  permissionTitle: {
    textAlign: 'center',
    color: '#FFFFFF',
  },
  permissionBody: {
    textAlign: 'center',
    lineHeight: 22,
    maxWidth: 320,
  },
  settingsButton: {
    marginTop: Spacing.two,
    borderRadius: Spacing.three,
    paddingHorizontal: Spacing.five,
    paddingVertical: Spacing.three,
    minWidth: 200,
    alignItems: 'center',
  },
  settingsButtonText: {
    color: '#FFFFFF',
  },
  textButton: {
    paddingVertical: Spacing.two,
    paddingHorizontal: Spacing.four,
  },
  overlayPanel: {
    backgroundColor: 'rgba(0, 0, 0, 0.62)',
  },
  overlayRow: {
    flexDirection: 'row',
  },
  overlaySide: {
    flex: 1,
  },
  overlayBottom: {
    flex: 1,
  },
  viewfinder: {
    position: 'relative',
    overflow: 'hidden',
  },
  corner: {
    position: 'absolute',
    width: CORNER_SIZE,
    height: CORNER_SIZE,
    borderColor: '#FFFFFF',
  },
  cornerTopLeft: {
    top: 0,
    left: 0,
    borderTopWidth: CORNER_BORDER,
    borderLeftWidth: CORNER_BORDER,
    borderTopLeftRadius: Spacing.two,
  },
  cornerTopRight: {
    top: 0,
    right: 0,
    borderTopWidth: CORNER_BORDER,
    borderRightWidth: CORNER_BORDER,
    borderTopRightRadius: Spacing.two,
  },
  cornerBottomLeft: {
    bottom: 0,
    left: 0,
    borderBottomWidth: CORNER_BORDER,
    borderLeftWidth: CORNER_BORDER,
    borderBottomLeftRadius: Spacing.two,
  },
  cornerBottomRight: {
    bottom: 0,
    right: 0,
    borderBottomWidth: CORNER_BORDER,
    borderRightWidth: CORNER_BORDER,
    borderBottomRightRadius: Spacing.two,
  },
  scanLine: {
    position: 'absolute',
    left: Spacing.two,
    right: Spacing.two,
    top: 0,
    height: 2,
    borderRadius: 1,
    opacity: 0.92,
    shadowColor: '#2563EB',
    shadowOpacity: 0.8,
    shadowRadius: 8,
    shadowOffset: { width: 0, height: 0 },
  },
  header: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    paddingHorizontal: Spacing.four,
    alignItems: 'flex-start',
  },
  closeButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  footer: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    paddingHorizontal: Spacing.four,
    alignItems: 'center',
  },
  footerText: {
    color: '#FFFFFF',
    textAlign: 'center',
    textShadowColor: 'rgba(0, 0, 0, 0.45)',
    textShadowOffset: { width: 0, height: 1 },
    textShadowRadius: 4,
  },
  lookupState: {
    alignItems: 'center',
    gap: Spacing.two,
  },
  errorState: {
    alignItems: 'center',
    gap: Spacing.three,
    width: '100%',
    maxWidth: 340,
  },
  errorBannerText: {
    color: '#FFFFFF',
    textAlign: 'center',
    backgroundColor: 'rgba(214, 69, 69, 0.85)',
    paddingHorizontal: Spacing.three,
    paddingVertical: Spacing.two,
    borderRadius: Spacing.two,
    overflow: 'hidden',
    width: '100%',
  },
  retryButton: {
    borderRadius: Spacing.three,
    paddingHorizontal: Spacing.four,
    paddingVertical: Spacing.two,
  },
  pressed: {
    opacity: 0.85,
  },
  disabled: {
    opacity: 0.5,
  },
});
