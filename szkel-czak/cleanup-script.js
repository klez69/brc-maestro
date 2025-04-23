/**
 * Skrypt do usuwania zbędnych plików Joomla
 * Ten skrypt tworzy listę plików, które można usunąć dla optymalizacji witryny.
 * Użycie:
 * 1. Wgraj ten plik na serwer
 * 2. Uruchom przez przeglądarkę http://twoja-domena.pl/cleanup-script.js
 * 3. Kopiuj wygenerowaną listę komend do usunięcia plików
 */

document.addEventListener('DOMContentLoaded', function () {
	// Tworzymy kontener na interfejs
	const container = document.createElement('div')
	container.style.fontFamily = 'Arial, sans-serif'
	container.style.maxWidth = '900px'
	container.style.margin = '20px auto'
	container.style.padding = '20px'
	container.style.border = '1px solid #ddd'
	container.style.borderRadius = '5px'
	container.style.boxShadow = '0 0 10px rgba(0,0,0,0.1)'
	document.body.appendChild(container)

	// Nagłówek
	const header = document.createElement('h1')
	header.textContent = 'Narzędzie do czyszczenia plików Joomla'
	header.style.color = '#333'
	container.appendChild(header)

	// Wprowadzenie
	const intro = document.createElement('p')
	intro.innerHTML =
		'To narzędzie pomoże bezpiecznie usunąć zbędne pliki Joomla, które zostały zastąpione przez zoptymalizowane wersje.<br>Zaznacz pliki i katalogi, które chcesz usunąć, i pobierz skrypt do użycia.'
	container.appendChild(intro)

	// Ostrzeżenie
	const warning = document.createElement('div')
	warning.style.padding = '10px'
	warning.style.backgroundColor = '#fff3cd'
	warning.style.color = '#856404'
	warning.style.borderRadius = '5px'
	warning.style.marginBottom = '20px'
	warning.innerHTML =
		'<strong>Uwaga!</strong> Przed użyciem tego narzędzia wykonaj kopię zapasową całej witryny. Usuwaj pliki etapami i sprawdzaj, czy witryna działa poprawnie po każdym etapie.'
	container.appendChild(warning)

	// Kategorie plików do usunięcia
	const categories = [
		{
			name: 'Pliki JavaScript',
			files: [
				{ path: 'media/jui/js/jquery-noconflict.js', description: 'Zastąpiony przez js/optimize.js' },
				{ path: 'media/jui/js/jquery-migrate.min.js', description: 'Zastąpiony przez js/optimize.js' },
				{ path: 'media/system/js/core.js', description: 'Zastąpiony przez js/optimize.js' },
				{
					path: 'components/com_sppagebuilder/assets/js/sppagebuilder.js',
					description: 'Zastąpiony przez js/optimize.js',
				},
				{
					path: 'components/com_sppagebuilder/assets/js/jquery.magnific-popup.min.js',
					description: 'Zastąpiony przez js/optimize.js',
				},
				{ path: 'templates/shaper_helixultimate/js/main.js', description: 'Zastąpiony przez js/optimize.js' },
				{ path: 'plugins/system/spcookieconsent/assets/js/script.js', description: 'Zastąpiony przez js/optimize.js' },
				{
					path: 'media/jui/js/bootstrap.min.js',
					description: 'Zastąpiony przez templates/shaper_helixultimate/js/bootstrap.bundle.min.js',
				},
			],
		},
		{
			name: 'Pliki CSS',
			files: [
				{
					path: 'components/com_sppagebuilder/assets/css/font-awesome.min.css',
					description: 'Zastąpiony przez css/optimize.css',
				},
				{
					path: 'components/com_sppagebuilder/assets/css/animate.min.css',
					description: 'Zastąpiony przez css/optimize.css',
				},
				{
					path: 'components/com_sppagebuilder/assets/css/sppagebuilder.css',
					description: 'Zastąpiony przez css/optimize.css',
				},
				{
					path: 'components/com_sppagebuilder/assets/css/sppagecontainer.css',
					description: 'Zastąpiony przez css/optimize.css',
				},
				{
					path: 'components/com_sppagebuilder/assets/css/magnific-popup.css',
					description: 'Zastąpiony przez css/optimize.css',
				},
				{
					path: 'plugins/system/helixultimate/assets/css/system-j3.min.css',
					description: 'Zastąpiony przez css/style.css',
				},
				{ path: 'templates/shaper_helixultimate/css/v4-shims.min.css', description: 'Zastąpiony przez css/style.css' },
				{ path: 'templates/shaper_helixultimate/css/template.css', description: 'Zastąpiony przez css/style.css' },
				{
					path: 'templates/shaper_helixultimate/css/presets/default.css',
					description: 'Zastąpiony przez css/style.css',
				},
				{
					path: 'plugins/system/spcookieconsent/assets/css/style.css',
					description: 'Zastąpiony przez css/optimize.css',
				},
			],
		},
		{
			name: 'Pliki systemowe i tymczasowe',
			files: [
				{ path: 'cache/*', description: 'Pliki cache - można bezpiecznie usunąć' },
				{ path: 'tmp/*', description: 'Pliki tymczasowe - można bezpiecznie usunąć' },
				{ path: 'administrator/backups/*', description: 'Stare kopie zapasowe - usunąć po wykonaniu nowej kopii' },
			],
		},
	]

	// Tworzymy formularz
	const form = document.createElement('form')
	container.appendChild(form)

	// Dodajemy opcje dla każdej kategorii
	categories.forEach(category => {
		const categoryHeader = document.createElement('h3')
		categoryHeader.textContent = category.name
		categoryHeader.style.marginTop = '20px'
		categoryHeader.style.borderBottom = '1px solid #ddd'
		categoryHeader.style.paddingBottom = '5px'
		form.appendChild(categoryHeader)

		// Checkbox do zaznaczenia/odznaczenia wszystkich w kategorii
		const selectAllDiv = document.createElement('div')
		selectAllDiv.style.marginBottom = '10px'

		const selectAllCheckbox = document.createElement('input')
		selectAllCheckbox.type = 'checkbox'
		selectAllCheckbox.id = `select-all-${category.name.replace(/\s+/g, '-').toLowerCase()}`
		selectAllDiv.appendChild(selectAllCheckbox)

		const selectAllLabel = document.createElement('label')
		selectAllLabel.htmlFor = selectAllCheckbox.id
		selectAllLabel.textContent = 'Zaznacz wszystkie'
		selectAllLabel.style.marginLeft = '5px'
		selectAllLabel.style.fontWeight = 'bold'
		selectAllDiv.appendChild(selectAllLabel)

		form.appendChild(selectAllDiv)

		// Dodajemy checkboxy dla plików w kategorii
		const filesList = document.createElement('div')
		filesList.style.marginLeft = '20px'

		category.files.forEach(file => {
			const fileDiv = document.createElement('div')
			fileDiv.style.marginBottom = '5px'

			const checkbox = document.createElement('input')
			checkbox.type = 'checkbox'
			checkbox.name = 'files'
			checkbox.value = file.path
			checkbox.id = `file-${file.path.replace(/[\/\.]/g, '-')}`
			checkbox.className = `category-${category.name.replace(/\s+/g, '-').toLowerCase()}`
			fileDiv.appendChild(checkbox)

			const label = document.createElement('label')
			label.htmlFor = checkbox.id
			label.innerHTML = `<strong>${file.path}</strong> - ${file.description}`
			label.style.marginLeft = '5px'
			fileDiv.appendChild(label)

			filesList.appendChild(fileDiv)
		})

		form.appendChild(filesList)

		// Dodajemy funkcję zaznaczania/odznaczania wszystkich
		selectAllCheckbox.addEventListener('change', function () {
			const checkboxes = document.querySelectorAll(`.category-${category.name.replace(/\s+/g, '-').toLowerCase()}`)
			checkboxes.forEach(cb => {
				cb.checked = this.checked
			})
		})
	})

	// Przyciski generowania skryptów
	const buttonDiv = document.createElement('div')
	buttonDiv.style.marginTop = '30px'
	buttonDiv.style.display = 'flex'
	buttonDiv.style.gap = '10px'
	form.appendChild(buttonDiv)

	// Przycisk do generowania skryptu bash
	const generateBashBtn = document.createElement('button')
	generateBashBtn.textContent = 'Generuj skrypt bash (Linux/Mac)'
	generateBashBtn.type = 'button'
	generateBashBtn.style.padding = '10px 15px'
	generateBashBtn.style.backgroundColor = '#4CAF50'
	generateBashBtn.style.color = 'white'
	generateBashBtn.style.border = 'none'
	generateBashBtn.style.borderRadius = '4px'
	generateBashBtn.style.cursor = 'pointer'
	buttonDiv.appendChild(generateBashBtn)

	// Przycisk do generowania skryptu Windows
	const generateBatBtn = document.createElement('button')
	generateBatBtn.textContent = 'Generuj skrypt batch (Windows)'
	generateBatBtn.type = 'button'
	generateBatBtn.style.padding = '10px 15px'
	generateBatBtn.style.backgroundColor = '#2196F3'
	generateBatBtn.style.color = 'white'
	generateBatBtn.style.border = 'none'
	generateBatBtn.style.borderRadius = '4px'
	generateBatBtn.style.cursor = 'pointer'
	buttonDiv.appendChild(generateBatBtn)

	// Miejsce na wygenerowany skrypt
	const scriptOutput = document.createElement('div')
	scriptOutput.style.marginTop = '30px'
	container.appendChild(scriptOutput)

	// Funkcja generująca skrypt bash
	generateBashBtn.addEventListener('click', function () {
		const selectedFiles = Array.from(document.querySelectorAll('input[name="files"]:checked')).map(cb => cb.value)

		if (selectedFiles.length === 0) {
			alert('Wybierz przynajmniej jeden plik do usunięcia')
			return
		}

		let scriptContent = '#!/bin/bash\n\n'
		scriptContent += '# Skrypt do usunięcia zbędnych plików Joomla\n'
		scriptContent += '# Wygenerowano: ' + new Date().toLocaleString() + '\n\n'

		scriptContent += 'echo "Rozpoczynanie usuwania zbędnych plików..."\n\n'
		scriptContent += 'JOOMLA_ROOT="/ścieżka/do/twojego/joomla" # ZMIEŃ TĘ ŚCIEŻKĘ!\n\n'

		scriptContent += '# Funkcja do usuwania pliku lub katalogu\n'
		scriptContent += 'remove_file() {\n'
		scriptContent += '  if [ -e "$1" ]; then\n'
		scriptContent += '    rm -rf "$1"\n'
		scriptContent += '    if [ $? -eq 0 ]; then\n'
		scriptContent += '      echo "✓ Usunięto: $1"\n'
		scriptContent += '    else\n'
		scriptContent += '      echo "✗ Błąd podczas usuwania: $1"\n'
		scriptContent += '    fi\n'
		scriptContent += '  else\n'
		scriptContent += '    echo "- Plik nie istnieje: $1"\n'
		scriptContent += '  fi\n'
		scriptContent += '}\n\n'

		scriptContent += '# Upewnij się, że skrypt jest uruchamiany z katalogu głównego Joomla\n'
		scriptContent += 'cd "$JOOMLA_ROOT" || { echo "Nie można przejść do katalogu $JOOMLA_ROOT"; exit 1; }\n\n'

		scriptContent += 'echo "Tworzenie kopii zapasowej wybranych plików..."\n'
		scriptContent += 'BACKUP_DIR="./backup_before_cleanup_$(date +%Y%m%d_%H%M%S)"\n'
		scriptContent += 'mkdir -p "$BACKUP_DIR"\n\n'

		// Najpierw tworzymy kopie zapasowe
		selectedFiles.forEach(file => {
			if (file.includes('*')) {
				const dirPath = file.replace('/*', '')
				scriptContent += `if [ -d "${dirPath}" ]; then\n`
				scriptContent += `  cp -r "${dirPath}" "$BACKUP_DIR/"\n`
				scriptContent += `  echo "✓ Utworzono kopię zapasową: ${dirPath}"\n`
				scriptContent += 'fi\n'
			} else {
				scriptContent += `if [ -f "${file}" ]; then\n`
				scriptContent += `  mkdir -p "$BACKUP_DIR/$(dirname "${file}")"\n`
				scriptContent += `  cp "${file}" "$BACKUP_DIR/${file}"\n`
				scriptContent += `  echo "✓ Utworzono kopię zapasową: ${file}"\n`
				scriptContent += 'fi\n'
			}
		})

		scriptContent += '\necho "Usuwanie plików..."\n\n'

		// Teraz usuwamy pliki
		selectedFiles.forEach(file => {
			if (file.includes('*')) {
				const dirPath = file.replace('/*', '')
				scriptContent += `if [ -d "${dirPath}" ]; then\n`
				scriptContent += `  find "${dirPath}" -type f -delete\n`
				scriptContent += `  echo "✓ Usunięto pliki z: ${dirPath}"\n`
				scriptContent += 'fi\n'
			} else {
				scriptContent += `remove_file "${file}"\n`
			}
		})

		scriptContent += '\necho "Operacja zakończona. Kopia zapasowa została utworzona w: $BACKUP_DIR"\n'

		// Wyświetlamy skrypt
		scriptOutput.innerHTML = '<h3>Wygenerowany skrypt bash:</h3>'
		const pre = document.createElement('pre')
		pre.style.backgroundColor = '#f5f5f5'
		pre.style.padding = '15px'
		pre.style.borderRadius = '5px'
		pre.style.overflowX = 'auto'
		pre.textContent = scriptContent
		scriptOutput.appendChild(pre)

		// Dodajemy przycisk do pobrania skryptu
		const downloadBtn = document.createElement('button')
		downloadBtn.textContent = 'Pobierz skrypt bash'
		downloadBtn.style.padding = '10px 15px'
		downloadBtn.style.backgroundColor = '#4CAF50'
		downloadBtn.style.color = 'white'
		downloadBtn.style.border = 'none'
		downloadBtn.style.borderRadius = '4px'
		downloadBtn.style.cursor = 'pointer'
		downloadBtn.style.marginTop = '10px'
		scriptOutput.appendChild(downloadBtn)

		downloadBtn.addEventListener('click', function () {
			const blob = new Blob([scriptContent], { type: 'text/plain' })
			const url = URL.createObjectURL(blob)
			const a = document.createElement('a')
			a.href = url
			a.download = 'cleanup_joomla.sh'
			document.body.appendChild(a)
			a.click()
			document.body.removeChild(a)
			URL.revokeObjectURL(url)
		})
	})

	// Funkcja generująca skrypt Windows batch
	generateBatBtn.addEventListener('click', function () {
		const selectedFiles = Array.from(document.querySelectorAll('input[name="files"]:checked')).map(cb => cb.value)

		if (selectedFiles.length === 0) {
			alert('Wybierz przynajmniej jeden plik do usunięcia')
			return
		}

		let scriptContent = '@echo off\r\n'
		scriptContent += 'rem Skrypt do usunięcia zbędnych plików Joomla\r\n'
		scriptContent += 'rem Wygenerowano: ' + new Date().toLocaleString() + '\r\n\r\n'

		scriptContent += 'echo Rozpoczynanie usuwania zbędnych plików...\r\n\r\n'
		scriptContent += 'set JOOMLA_ROOT=C:\\ścieżka\\do\\twojego\\joomla\r\n' // ZMIEŃ TĘ ŚCIEŻKĘ!
		scriptContent += 'rem WAŻNE: Zmień powyższą ścieżkę na właściwą ścieżkę do Joomla!\r\n\r\n'

		scriptContent += 'rem Przejście do katalogu głównego Joomla\r\n'
		scriptContent += 'cd /d %JOOMLA_ROOT% || echo Nie można przejść do katalogu %JOOMLA_ROOT% && exit /b 1\r\n\r\n'

		scriptContent += 'echo Tworzenie kopii zapasowej wybranych plików...\r\n'
		scriptContent += 'set TIMESTAMP=%date:~-4,4%%date:~-7,2%%date:~-10,2%_%time:~0,2%%time:~3,2%%time:~6,2%\r\n'
		scriptContent += 'set TIMESTAMP=%TIMESTAMP: =0%\r\n'
		scriptContent += 'set BACKUP_DIR=.\\backup_before_cleanup_%TIMESTAMP%\r\n'
		scriptContent += 'mkdir "%BACKUP_DIR%"\r\n\r\n'

		// Najpierw tworzymy kopie zapasowe
		selectedFiles.forEach(file => {
			// Zamieniamy slashe na backslashe dla Windows
			const winFile = file.replace(/\//g, '\\')

			if (file.includes('*')) {
				const dirPath = winFile.replace('\\*', '')
				scriptContent += `if exist "${dirPath}\\" (\r\n`
				scriptContent += `  xcopy "${dirPath}" "%BACKUP_DIR%\\${dirPath}" /E /I /Y\r\n`
				scriptContent += `  echo ✓ Utworzono kopię zapasową: ${dirPath}\r\n`
				scriptContent += ')\r\n'
			} else {
				scriptContent += `if exist "${winFile}" (\r\n`
				scriptContent += `  if not exist "%BACKUP_DIR%\\${winFile.substring(
					0,
					winFile.lastIndexOf('\\')
				)}" mkdir "%BACKUP_DIR%\\${winFile.substring(0, winFile.lastIndexOf('\\'))}"\r\n`
				scriptContent += `  copy "${winFile}" "%BACKUP_DIR%\\${winFile}" /Y\r\n`
				scriptContent += `  echo ✓ Utworzono kopię zapasową: ${winFile}\r\n`
				scriptContent += ')\r\n'
			}
		})

		scriptContent += '\r\necho Usuwanie plików...\r\n\r\n'

		// Teraz usuwamy pliki
		selectedFiles.forEach(file => {
			// Zamieniamy slashe na backslashe dla Windows
			const winFile = file.replace(/\//g, '\\')

			if (file.includes('*')) {
				const dirPath = winFile.replace('\\*', '')
				scriptContent += `if exist "${dirPath}\\" (\r\n`
				scriptContent += `  del /Q /S "${dirPath}\\*.*"\r\n`
				scriptContent += `  echo ✓ Usunięto pliki z: ${dirPath}\r\n`
				scriptContent += ') else (\r\n'
				scriptContent += `  echo - Katalog nie istnieje: ${dirPath}\r\n`
				scriptContent += ')\r\n'
			} else {
				scriptContent += `if exist "${winFile}" (\r\n`
				scriptContent += `  del /F "${winFile}"\r\n`
				scriptContent += `  if not exist "${winFile}" (\r\n`
				scriptContent += `    echo ✓ Usunięto: ${winFile}\r\n`
				scriptContent += `  ) else (\r\n`
				scriptContent += `    echo ✗ Błąd podczas usuwania: ${winFile}\r\n`
				scriptContent += `  )\r\n`
				scriptContent += ') else (\r\n'
				scriptContent += `  echo - Plik nie istnieje: ${winFile}\r\n`
				scriptContent += ')\r\n'
			}
		})

		scriptContent += '\r\necho Operacja zakończona. Kopia zapasowa została utworzona w: %BACKUP_DIR%\r\n'
		scriptContent += 'pause\r\n'

		// Wyświetlamy skrypt
		scriptOutput.innerHTML = '<h3>Wygenerowany skrypt Windows batch:</h3>'
		const pre = document.createElement('pre')
		pre.style.backgroundColor = '#f5f5f5'
		pre.style.padding = '15px'
		pre.style.borderRadius = '5px'
		pre.style.overflowX = 'auto'
		pre.textContent = scriptContent
		scriptOutput.appendChild(pre)

		// Dodajemy przycisk do pobrania skryptu
		const downloadBtn = document.createElement('button')
		downloadBtn.textContent = 'Pobierz skrypt batch'
		downloadBtn.style.padding = '10px 15px'
		downloadBtn.style.backgroundColor = '#2196F3'
		downloadBtn.style.color = 'white'
		downloadBtn.style.border = 'none'
		downloadBtn.style.borderRadius = '4px'
		downloadBtn.style.cursor = 'pointer'
		downloadBtn.style.marginTop = '10px'
		scriptOutput.appendChild(downloadBtn)

		downloadBtn.addEventListener('click', function () {
			const blob = new Blob([scriptContent], { type: 'text/plain' })
			const url = URL.createObjectURL(blob)
			const a = document.createElement('a')
			a.href = url
			a.download = 'cleanup_joomla.bat'
			document.body.appendChild(a)
			a.click()
			document.body.removeChild(a)
			URL.revokeObjectURL(url)
		})
	})
})

// Tworzymy prosty HTML dla skryptu
document.write(`
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Narzędzie do czyszczenia plików Joomla</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }
        button:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <!-- Kontener dla skryptu zostanie tutaj wstawiony -->
</body>
</html>
`)
