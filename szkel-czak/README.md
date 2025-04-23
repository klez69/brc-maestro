# Dokumentacja optymalizacji witryny Joomla

## Opis projektu

Ten projekt zawiera pliki i narzędzia służące do optymalizacji witryny opartej na systemie Joomla. Optymalizacja polega na zastąpieniu wielu mniejszych plików CSS i JavaScript przez zoptymalizowane, scalone wersje, co prowadzi do zmniejszenia liczby żądań HTTP i poprawy wydajności strony.

## Struktura projektu

```
.
├── backup/                   # Katalog z kopiami zapasowymi
│   ├── index.html.bak        # Kopia zapasowa głównej strony
│   ├── custom-style.css.bak  # Kopia zapasowa niestandardowych stylów
│   └── style.css.bak         # Kopia zapasowa głównego arkusza stylów
├── js/
│   └── optimize.js           # Zoptymalizowany plik JavaScript zastępujący wiele mniejszych plików JS
├── css/
│   ├── style.css             # Główny arkusz stylów
│   ├── custom-style.css      # Niestandardowe style
│   └── optimize.css          # Zoptymalizowany plik CSS zastępujący wiele mniejszych plików CSS
├── index.html                # Zoptymalizowana wersja głównej strony
├── cleanup.html              # Interfejs do narzędzia czyszczenia
├── cleanup-script.js         # Skrypt do czyszczenia zbędnych plików
├── joomla-cleanup.md         # Dokumentacja dotycząca plików do usunięcia
└── joomla-removable-files.txt # Szczegółowa lista plików do usunięcia
```

## Wykonane optymalizacje

1. **Optymalizacja JavaScript**:

   - Utworzono pojedynczy plik `js/optimize.js`, który zastępuje wiele oddzielnych plików JS
   - Zaimplementowano efektywniejsze funkcje i usunięto nadmiarowy kod
   - Zminimalizowano zależności zewnętrzne

2. **Optymalizacja CSS**:

   - Utworzono pliki `css/optimize.css` i zaktualizowano `css/style.css`, które zastępują wiele mniejszych plików CSS
   - Uporządkowano i zunifikowano style
   - Wprowadzono nowoczesne techniki CSS

3. **Optymalizacja HTML**:
   - Zredukowano liczbę wczytywanych plików w `index.html`
   - Usunięto niepotrzebne skrypty i style
   - Zaimplementowano ładowanie zasobów w sposób bardziej wydajny

## Narzędzia do czyszczenia

Projekt zawiera narzędzia do bezpiecznego usunięcia zbędnych plików Joomla, które nie są już potrzebne po optymalizacji:

1. **cleanup.html** - Interfejs użytkownika do narzędzia czyszczenia
2. **cleanup-script.js** - Generator skryptów do usuwania plików
3. **joomla-removable-files.txt** - Szczegółowa lista plików, które można bezpiecznie usunąć

### Jak używać narzędzia czyszczenia

1. Wgraj pliki `cleanup.html` i `cleanup-script.js` na serwer
2. Otwórz plik `cleanup.html` w przeglądarce
3. Postępuj zgodnie z instrukcjami na stronie:
   - Wybierz pliki do usunięcia
   - Wygeneruj odpowiedni skrypt (bash dla Linux/Mac, batch dla Windows)
   - Pobierz skrypt i przenieś go do katalogu głównego Joomla
   - Przed uruchomieniem, zmodyfikuj ścieżkę w skrypcie
   - Uruchom skrypt, aby usunąć zbędne pliki

## Uwagi dotyczące bezpieczeństwa

- **Zawsze** wykonaj kopię zapasową całej witryny przed rozpoczęciem usuwania plików
- Usuwaj pliki etapami i testuj witrynę po każdym etapie
- Skrypty automatycznie tworzą kopie zapasowe usuwanych plików
- W przypadku problemów, można przywrócić pliki z kopii zapasowej

## Lista zastąpionych plików

### Pliki JavaScript zastąpione przez js/optimize.js:

- media/jui/js/jquery-noconflict.js
- media/jui/js/jquery-migrate.min.js
- media/system/js/core.js
- components/com_sppagebuilder/assets/js/sppagebuilder.js
- components/com_sppagebuilder/assets/js/jquery.magnific-popup.min.js
- templates/shaper_helixultimate/js/main.js
- plugins/system/spcookieconsent/assets/js/script.js

### Pliki CSS zastąpione przez css/optimize.css i css/style.css:

- components/com_sppagebuilder/assets/css/font-awesome.min.css
- components/com_sppagebuilder/assets/css/animate.min.css
- components/com_sppagebuilder/assets/css/sppagebuilder.css
- components/com_sppagebuilder/assets/css/sppagecontainer.css
- components/com_sppagebuilder/assets/css/magnific-popup.css
- plugins/system/helixultimate/assets/css/system-j3.min.css
- templates/shaper_helixultimate/css/v4-shims.min.css
- templates/shaper_helixultimate/css/template.css
- templates/shaper_helixultimate/css/presets/default.css
- plugins/system/spcookieconsent/assets/css/style.css

## Dodatkowa dokumentacja

Szczegółowe informacje na temat plików, które można usunąć, znajdują się w pliku `joomla-cleanup.md`.
