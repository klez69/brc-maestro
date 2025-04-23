# Optymalizacja projektu - pliki Joomla do usunięcia

Poniższe pliki i katalogi można bezpiecznie usunąć, aby zoptymalizować stronę przy założeniu, że używamy teraz własnego, zoptymalizowanego kodu.

## Foldery JavaScript do usunięcia

- `media/jui/js/` - Większość plików JS z tego katalogu została zastąpiona przez `js/optimize.js`
- `media/system/js/` - Pliki systemowe JS zostały zoptymalizowane
- `media/vendor/jquery/js/` - Używamy nowszej wersji jQuery lub rezygnujemy z niej
- `media/mod_*/js/` - Pliki JS związane z nieużywanymi modułami
- `media/com_*/js/` - Pliki JS związane z nieużywanymi komponentami

## Foldery CSS do usunięcia

- `media/jui/css/` - Style zostały zastąpione przez `css/optimize.css` i `css/style.css`
- `media/system/css/` - Systemowe style zostały zoptymalizowane
- `media/vendor/*/css/` - Style bibliotek zewnętrznych zostały zoptymalizowane
- `media/mod_*/css/` - Style związane z nieużywanymi modułami
- `media/com_*/css/` - Style związane z nieużywanymi komponentami

## Pliki obrazów do optymalizacji (nie usunięcia)

- Wszystkie obrazy powinny zostać zoptymalizowane (skompresowane) przy użyciu narzędzi takich jak TinyPNG lub podobnych
- Niepotrzebne ikony z `media/system/images/` mogą zostać usunięte

## Inne pliki do usunięcia

- Pliki cache w `cache/`
- Tymczasowe pliki w `tmp/`
- Kopie zapasowe w `administrator/backups/` (po wykonaniu aktualnej kopii)
- Niewykorzystywane rozszerzenia w `components/`, `modules/`, `plugins/`
- Nieużywane szablony w `templates/` (poza aktywnym)
- Instalatory w `installation/`

## Instrukcje wdrożenia

1. **Przed usunięciem plików**:

   - Wykonaj pełną kopię zapasową strony
   - Przetestuj stronę z nowymi plikami `optimize.css` i `optimize.js`
   - Sprawdź działanie wszystkich funkcji na stronie

2. **Po usunięciu plików**:

   - Ponownie przetestuj stronę w różnych przeglądarkach
   - Sprawdź konsole przeglądarek pod kątem błędów JavaScript
   - Upewnij się, że wszystkie funkcje strony działają poprawnie

3. **Optymalizacja serwera**:
   - Włącz kompresję GZIP
   - Skonfiguruj cache przeglądarki (długi czas wygasania dla statycznych zasobów)
   - Rozważ użycie CDN dla plików statycznych

## Uwagi dodatkowe

- Usunięcie tych plików znacznie zmniejszy rozmiar strony i przyspieszy jej ładowanie
- W przypadku pojawienia się problemów, można łatwo przywrócić usunięte pliki z kopii zapasowej
- Szczególną uwagę należy zwrócić na komponenty, które mogą być używane w innych częściach strony

Pamiętaj, że optymalizacja powinna być procesem stopniowym - usuwaj pliki partiami i testuj po każdej zmianie.
