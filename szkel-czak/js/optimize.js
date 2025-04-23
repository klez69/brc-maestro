/**
 * Zoptymalizowany skrypt zastępujący funkcjonalność Joomla
 * Wersja: 1.0.0
 * Data: 2024
 */

// Funkcje pomocnicze
const OptimizeJS = {
	// Funkcja inicjalizująca wszystkie komponenty
	init: function () {
		this.initButtonEffects()
		this.initSmoothScroll()
		this.initCookieConsent()
		this.initAnimations()
		this.initResponsiveMenu()

		// Dodajemy komunikat w konsoli na potrzeby debugowania
		console.log('OptimizeJS została zainicjalizowana pomyślnie.')
	},

	// Efekty dla przycisków
	initButtonEffects: function () {
		// Obsługa efektu ripple
		document.querySelectorAll('.btn-modern').forEach(button => {
			button.addEventListener('mousedown', function (e) {
				const rect = button.getBoundingClientRect()
				const x = e.clientX - rect.left
				const y = e.clientY - rect.top

				const ripple = document.createElement('span')
				ripple.className = 'ripple'
				ripple.style.width = ripple.style.height = Math.max(button.offsetWidth, button.offsetHeight) + 'px'
				ripple.style.left = x - ripple.offsetWidth / 2 + 'px'
				ripple.style.top = y - ripple.offsetHeight / 2 + 'px'

				button.appendChild(ripple)

				setTimeout(() => {
					button.removeChild(ripple)
				}, 600)
			})

			// Efekt poświaty przy najechaniu
			button.addEventListener('mouseenter', function () {
				button.classList.add('glow')
			})

			button.addEventListener('mouseleave', function () {
				button.classList.remove('glow')
			})
		})
	},

	// Płynne przewijanie do kotwic na stronie
	initSmoothScroll: function () {
		document.querySelectorAll('a[href^="#"]').forEach(anchor => {
			anchor.addEventListener('click', function (e) {
				const targetId = this.getAttribute('href')
				if (targetId === '#') return

				const targetElement = document.querySelector(targetId)
				if (targetElement) {
					e.preventDefault()
					window.scrollTo({
						top: targetElement.offsetTop - 80,
						behavior: 'smooth',
					})
				}
			})
		})
	},

	// Obsługa cookie consent
	initCookieConsent: function () {
		const cookieConsent = document.getElementById('sp-cookie-consent')
		if (!cookieConsent) return

		// Sprawdź czy zgoda już została wyrażona
		if (!localStorage.getItem('cookie-consent-accepted')) {
			cookieConsent.style.display = 'block'

			// Obsługa przycisku akceptacji
			const acceptButton = document.querySelector('.sp-cookie-close')
			if (acceptButton) {
				acceptButton.addEventListener('click', function (e) {
					e.preventDefault()
					localStorage.setItem('cookie-consent-accepted', 'true')
					cookieConsent.style.display = 'none'
				})
			}
		}
	},

	// Obsługa animacji
	initAnimations: function () {
		const animatedElements = document.querySelectorAll('.sppb-wow')

		// Funkcja sprawdzająca czy element jest w widoku
		const isElementInViewport = function (el) {
			const rect = el.getBoundingClientRect()
			return rect.top <= (window.innerHeight || document.documentElement.clientHeight) && rect.bottom >= 0
		}

		// Funkcja uruchamiająca animacje
		const startAnimation = function () {
			animatedElements.forEach(element => {
				if (isElementInViewport(element) && element.style.visibility === 'hidden') {
					element.style.visibility = 'visible'
					element.classList.add('animated')

					// Pobierz typ animacji z atrybutu data
					const animationType = element.getAttribute('data-sppb-wow-animation')
					if (animationType) {
						element.classList.add(animationType)
					}

					// Pobierz opóźnienie z atrybutu data
					const delay = element.getAttribute('data-sppb-wow-delay')
					if (delay) {
						element.style.animationDelay = delay
					}
				}
			})
		}

		// Uruchom animacje na scroll
		window.addEventListener('scroll', startAnimation)

		// Uruchom też animacje przy załadowaniu strony
		window.addEventListener('load', startAnimation)
	},

	// Menu responsywne
	initResponsiveMenu: function () {
		const menuToggle = document.querySelector('.offcanvas-menu-toggler')
		const offcanvasMenu = document.querySelector('.offcanvas-menu')
		const closeButton = document.querySelector('.close-offcanvas')

		if (menuToggle && offcanvasMenu) {
			menuToggle.addEventListener('click', function () {
				offcanvasMenu.classList.add('menu-open')
			})

			if (closeButton) {
				closeButton.addEventListener('click', function () {
					offcanvasMenu.classList.remove('menu-open')
				})
			}
		}
	},
}

// Inicjalizacja po załadowaniu DOM
document.addEventListener('DOMContentLoaded', function () {
	OptimizeJS.init()
})
