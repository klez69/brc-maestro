document.addEventListener('DOMContentLoaded', function () {
	// Funkcja do wymuszania poziomego menu
	function enforceHorizontalMenu() {
		console.log('Wymuszanie poziomego menu...')

		// Znajdź główny kontener menu
		const menuContainer = document.querySelector('.sp-megamenu-parent')

		if (menuContainer) {
			console.log('Znaleziono kontener menu')

			// Ustaw style bezpośrednio na kontenerze menu
			menuContainer.style.display = 'flex'
			menuContainer.style.flexDirection = 'row'
			menuContainer.style.flexWrap = 'wrap'
			menuContainer.style.justifyContent = 'center'
			menuContainer.style.width = '100%'

			// Zastosuj style na elementach menu
			const menuItems = menuContainer.querySelectorAll('li')
			menuItems.forEach(item => {
				item.style.display = 'inline-block'
				item.style.float = 'none'
				item.style.verticalAlign = 'middle'
			})

			// Dodaj style dla podmenu
			const dropdownMenus = menuContainer.querySelectorAll('.sp-dropdown')
			dropdownMenus.forEach(dropdown => {
				dropdown.style.position = 'absolute'
				dropdown.style.top = '100%'
				dropdown.style.left = '0'
				dropdown.style.zIndex = '100'
			})

			console.log('Style poziomego menu zastosowane')
		} else {
			console.log('Nie znaleziono kontenera menu')

			// Alternatywna próba znalezienia menu
			const alternativeMenu = document.querySelector('.sp-menu')
			if (alternativeMenu) {
				console.log('Znaleziono alternatywny kontener menu')
				alternativeMenu.style.display = 'flex'
				alternativeMenu.style.flexDirection = 'row'

				const altMenuItems = alternativeMenu.querySelectorAll('li')
				altMenuItems.forEach(item => {
					item.style.display = 'inline-block'
					item.style.float = 'none'
				})
			} else {
				console.log('Nie znaleziono żadnego kontenera menu')
			}
		}
	}

	// Dodanie własnego arkusza stylów CSS z wyższym priorytetem
	function addCustomCSS() {
		if (!document.getElementById('custom-menu-styles')) {
			console.log('Dodawanie własnego arkusza stylów CSS')
			const style = document.createElement('style')
			style.id = 'custom-menu-styles'
			style.innerHTML = `
				.sp-megamenu-parent {
					display: flex !important;
					flex-direction: row !important;
					flex-wrap: wrap !important;
					justify-content: center !important;
					width: 100% !important;
					float: none !important;
				}
				.sp-megamenu-parent > li {
					display: inline-block !important;
					float: none !important;
					vertical-align: middle !important;
				}
				.sp-megamenu-parent .sp-dropdown {
					position: absolute !important;
					top: 100% !important;
					left: 0 !important;
					z-index: 100 !important;
				}
				@media (max-width: 991px) {
					.sp-megamenu-parent {
						display: flex !important;
					}
				}
			`
			document.head.appendChild(style)
			console.log('Własny arkusz CSS dodany')
		}
	}

	// Wywołaj funkcje
	addCustomCSS()

	// Wywołaj funkcję po małym opóźnieniu na wypadek, gdyby menu było dodawane dynamicznie
	setTimeout(enforceHorizontalMenu, 500)

	// Wywołaj ponownie przy zmianie rozmiaru okna
	window.addEventListener('resize', enforceHorizontalMenu)

	// Wywołaj ponownie po pełnym załadowaniu strony
	window.addEventListener('load', function () {
		enforceHorizontalMenu()
		setTimeout(enforceHorizontalMenu, 1000)
	})

	// Obserwator mutacji DOM, aby przechwycić dynamiczne zmiany w menu
	const observer = new MutationObserver(function (mutations) {
		mutations.forEach(function (mutation) {
			if (mutation.type === 'childList' || mutation.type === 'attributes') {
				enforceHorizontalMenu()
			}
		})
	})

	// Obserwuj zmiany w całym dokumencie
	const config = { attributes: true, childList: true, subtree: true }
	observer.observe(document.body, config)

	console.log('Inicjalizacja skryptu menu zakończona')

	// Dodaj odnośnik do pliku CSS, jeśli nie istnieje
	function addCustomCssIfNeeded() {
		if (!document.querySelector('link[href*="custom-style.css"]')) {
			const customCss = document.createElement('link')
			customCss.rel = 'stylesheet'
			customCss.type = 'text/css'
			customCss.href = '/css/custom-style.css'
			document.head.appendChild(customCss)
			console.log('Dodano link do custom-style.css')
		}
	}

	addCustomCssIfNeeded()
})
