# Dokumentacja Systemu Śledzenia Odwiedzających

System śledzenia odwiedzających pozwala na monitorowanie aktywnych użytkowników na stronie oraz przechowywanie historii odwiedzin.

## Spis treści

1. [Opis systemu](#opis-systemu)
2. [Instalacja](#instalacja)
3. [Struktura bazy danych](#struktura-bazy-danych)
4. [Panel administracyjny](#panel-administracyjny)
5. [API](#api)
6. [Rozwiązywanie problemów](#rozwiązywanie-problemów)

## Opis systemu

System śledzenia odwiedzających składa się z następujących komponentów:

- **Śledzenie aktywnych odwiedzających** - pokazuje, kto aktualnie przebywa na stronie
- **Historia odwiedzin** - zapisuje wszystkie odwiedziny w bazie danych
- **Panel administracyjny** - interfejs do przeglądania danych
- **API** - endpoints do pobierania i aktualizacji danych

## Instalacja

1. Uruchom skrypt instalacyjny `setup_visitor_tracking.php` w przeglądarce
2. Skrypt utworzy niezbędne tabele w bazie danych oraz sprawdzi uprawnienia katalogów
3. Po pomyślnej instalacji można korzystać z systemu

## Struktura bazy danych

System wykorzystuje dwie tabele i jeden widok:

### Tabela `visitors`

Przechowuje informacje o aktywnych odwiedzających.

| Kolumna           | Typ          | Opis                                    |
| ----------------- | ------------ | --------------------------------------- |
| id                | VARCHAR(50)  | Unikalny identyfikator odwiedzającego   |
| page              | VARCHAR(255) | Adres URL odwiedzanej strony            |
| referrer          | TEXT         | Strona, z której przyszedł odwiedzający |
| user_agent        | VARCHAR(255) | Informacje o przeglądarce               |
| screen_resolution | VARCHAR(20)  | Rozdzielczość ekranu                    |
| language          | VARCHAR(10)  | Język przeglądarki                      |
| ip_address        | VARCHAR(45)  | Adres IP                                |
| last_activity     | TIMESTAMP    | Czas ostatniej aktywności               |
| created_at        | TIMESTAMP    | Czas pierwszego odwiedzenia             |

### Tabela `visitor_history`

Przechowuje historię wszystkich odwiedzin.

| Kolumna           | Typ          | Opis                                          |
| ----------------- | ------------ | --------------------------------------------- |
| id                | INT          | Unikalny identyfikator wpisu (auto increment) |
| visitor_id        | VARCHAR(50)  | Identyfikator odwiedzającego                  |
| page              | VARCHAR(255) | Adres URL odwiedzanej strony                  |
| referrer          | TEXT         | Strona, z której przyszedł odwiedzający       |
| user_agent        | VARCHAR(255) | Informacje o przeglądarce                     |
| screen_resolution | VARCHAR(20)  | Rozdzielczość ekranu                          |
| language          | VARCHAR(10)  | Język przeglądarki                            |
| ip_address        | VARCHAR(45)  | Adres IP                                      |
| visit_time        | TIMESTAMP    | Czas odwiedzin                                |

### Widok `visitor_statistics`

Agreguje dane z tabeli `visitor_history` do celów raportowania.

| Kolumna         | Opis                             |
| --------------- | -------------------------------- |
| visit_date      | Data odwiedzin                   |
| total_visits    | Łączna liczba odwiedzin          |
| unique_visitors | Liczba unikalnych odwiedzających |
| unique_ips      | Liczba unikalnych adresów IP     |

## Panel administracyjny

Panel administracyjny umożliwia dostęp do informacji o odwiedzających. Główne strony panelu to:

- `admin/visitors.html` - Aktywni odwiedzający w czasie rzeczywistym
- `admin/visitor_history.html` - Historia odwiedzin
- `admin/dashboard.html` - Pulpit z podsumowaniem statystyk

## API

System udostępnia następujące endpointy API:

### 1. Pobieranie aktywnych odwiedzających

```
GET visitors_api.php?action=get
```

**Przykładowa odpowiedź:**

```json
{
	"status": "success",
	"visitors": [
		{
			"id": "v_5f8d3a1e2b4c9",
			"page": "/index.html",
			"referrer": "https://google.com",
			"user_agent": "Mozilla/5.0...",
			"screen_resolution": "1920x1080",
			"language": "pl",
			"ip_address": "127.0.0.1",
			"last_activity": "2023-06-15 14:30:45"
		}
	]
}
```

### 2. Pobieranie historii odwiedzin

```
GET track_visit.php?action=history&days=7&limit=100
```

**Parametry:**

- `days` - liczba dni wstecz do pobrania (domyślnie 7)
- `limit` - maksymalna liczba wyników (domyślnie 100)

**Przykładowa odpowiedź:**

```json
{
	"status": "success",
	"history": [
		{
			"id": 123,
			"visitor_id": "v_5f8d3a1e2b4c9",
			"page": "/index.html",
			"referrer": "https://google.com",
			"user_agent": "Mozilla/5.0...",
			"screen_resolution": "1920x1080",
			"language": "pl",
			"ip_address": "127.0.0.1",
			"visit_time": "2023-06-15 14:30:45"
		}
	]
}
```

### 3. Aktualizacja statusu odwiedzającego

```
POST track_visit.php?action=update
```

**Przykładowe dane POST (JSON):**

```json
{
	"page": "/contact.html",
	"referrer": "https://example.com",
	"user_agent": "Mozilla/5.0...",
	"screen_resolution": "1920x1080",
	"language": "pl"
}
```

**Przykładowa odpowiedź:**

```json
{
	"status": "success",
	"visitor_id": "v_5f8d3a1e2b4c9"
}
```

## Rozwiązywanie problemów

### Dane o odwiedzających nie są zapisywane

1. Sprawdź, czy skrypt śledzący jest poprawnie dodany do strony
2. Upewnij się, że połączenie z bazą danych działa poprawnie
3. Sprawdź uprawnienia do katalogu `logs`
4. Sprawdź plik logów `logs/visitor_tracking.log` w poszukiwaniu błędów

### Brak danych w panelu administracyjnym

1. Upewnij się, że jesteś zalogowany jako administrator
2. Sprawdź połączenie sieciowe w konsoli przeglądarki
3. Sprawdź, czy zapytania API zwracają poprawne odpowiedzi
