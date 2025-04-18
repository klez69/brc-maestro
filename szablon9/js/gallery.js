class GaleriaPortfolio {
	constructor() {
		this.elementyNaStrone = 8
		this.aktualnaStrona = 1
		this.aktualnyFiltr = 'all'
		this.elementy = []
		this.ladowanie = true
		this.baseUrl = document.querySelector('base')?.href || window.location.origin + '/salon/'

		this.kontenerSiatki = document.querySelector('.portfolio-grid')
		this.elementLadowania = document.querySelector('.loading')
		this.kontenerPaginacji = document.querySelector('.pagination')

		this.inicjalizujPrzyciski()
		this.wczytajElementy()
	}

	inicjalizujPrzyciski() {
		const przyciski = document.querySelectorAll('.filter-button')
		przyciski.forEach(przycisk => {
			przycisk.addEventListener('click', () => {
				const filtr = przycisk.dataset.filter
				this.ustawAktywnyFiltr(filtr)
				this.aktualnaStrona = 1
				this.aktualizujWyswietlanie()
			})
		})
	}

	ustawAktywnyFiltr(filtr) {
		const przyciski = document.querySelectorAll('.filter-button')
		przyciski.forEach(przycisk => {
			przycisk.classList.toggle('active', przycisk.dataset.filter === filtr)
		})
		this.aktualnyFiltr = filtr
	}

	async wczytajElementy() {
		try {
			console.log('Próba pobrania elementów portfolio...')
			const odpowiedz = await fetch(this.baseUrl + 'api/get-portfolio-items.php')
			if (!odpowiedz.ok) {
				const tekstBledu = await odpowiedz.text()
				console.error('Błąd odpowiedzi:', odpowiedz.status, tekstBledu)
				throw new Error(`Nie udało się załadować elementów portfolio (${odpowiedz.status})`)
			}

			const dane = await odpowiedz.json()
			console.log('Otrzymane dane:', dane)
			if (dane.success && Array.isArray(dane.items)) {
				this.elementy = dane.items.map(item => ({
					...item,
					image_url: this.baseUrl + item.image_url,
				}))
				this.ladowanie = false
				this.aktualizujWyswietlanie()
			} else {
				throw new Error(dane.error || dane.message || 'Nieprawidłowy format danych')
			}
		} catch (blad) {
			console.error('Błąd podczas ładowania elementów portfolio:', blad)
			this.pokazBlad('Wystąpił błąd podczas ładowania galerii. Spróbuj odświeżyć stronę.')
		}
	}

	filtrujElementy() {
		if (this.aktualnyFiltr === 'all') return this.elementy
		return this.elementy.filter(element => element.category === this.aktualnyFiltr)
	}

	aktualizujWyswietlanie() {
		const przefiltrowaneElementy = this.filtrujElementy()
		const poczatek = (this.aktualnaStrona - 1) * this.elementyNaStrone
		const elementyDoWyswietlenia = przefiltrowaneElementy.slice(poczatek, poczatek + this.elementyNaStrone)

		// Wyczyść i zaktualizuj siatkę
		this.kontenerSiatki.innerHTML = ''
		elementyDoWyswietlenia.forEach(element => {
			const elementHTML = this.stworzElementPortfolio(element)
			this.kontenerSiatki.insertAdjacentHTML('beforeend', elementHTML)
		})

		// Animuj elementy
		const elementy = this.kontenerSiatki.querySelectorAll('.portfolio-item')
		elementy.forEach((element, indeks) => {
			element.style.opacity = '0'
			setTimeout(() => {
				element.style.opacity = '1'
				element.style.transition = 'opacity 0.3s ease'
			}, indeks * 100)
		})

		this.aktualizujPaginacje(przefiltrowaneElementy.length)
		this.elementLadowania.style.display = this.ladowanie ? 'block' : 'none'
	}

	stworzElementPortfolio(element) {
		return `
			<div class="portfolio-item" data-category="${element.category}">
				<div class="portfolio-item-inner">
					<img 
						src="${element.image_url}" 
						alt="${element.title}"
						onerror="this.src='${this.baseUrl}images/42.jpg'; this.onerror=null;"
						loading="lazy"
					/>
					<div class="portfolio-item-overlay">
						<div class="portfolio-item-content">
							<h3>${element.title}</h3>
							<p>${element.description || ''}</p>
							<a href="${element.image_url}" class="portfolio-item-button" data-fslightbox>Zobacz więcej</a>
						</div>
					</div>
				</div>
			</div>
		`
	}

	aktualizujPaginacje(calkowitaIloscElementow) {
		const calkowitaIloscStron = Math.ceil(calkowitaIloscElementow / this.elementyNaStrone)

		if (!this.kontenerPaginacji || calkowitaIloscStron <= 1) return

		this.kontenerPaginacji.innerHTML = ''

		// Przycisk "Poprzednia"
		if (this.aktualnaStrona > 1) {
			const przyciskPoprzedni = this.stworzPrzyciskPaginacji('«', this.aktualnaStrona - 1)
			this.kontenerPaginacji.appendChild(przyciskPoprzedni)
		}

		// Przyciski numeryczne
		for (let i = 1; i <= calkowitaIloscStron; i++) {
			const przyciskStrony = this.stworzPrzyciskPaginacji(i, i)
			if (i === this.aktualnaStrona) przyciskStrony.classList.add('active')
			this.kontenerPaginacji.appendChild(przyciskStrony)
		}

		// Przycisk "Następna"
		if (this.aktualnaStrona < calkowitaIloscStron) {
			const przyciskNastepny = this.stworzPrzyciskPaginacji('»', this.aktualnaStrona + 1)
			this.kontenerPaginacji.appendChild(przyciskNastepny)
		}
	}

	stworzPrzyciskPaginacji(tekst, strona) {
		const przycisk = document.createElement('button')
		przycisk.className = 'pagination-button'
		przycisk.textContent = tekst
		przycisk.addEventListener('click', () => {
			this.aktualnaStrona = strona
			this.aktualizujWyswietlanie()
			document.getElementById('portfolio').scrollIntoView({ behavior: 'smooth' })
		})
		return przycisk
	}

	pokazBlad(wiadomosc) {
		if (this.kontenerSiatki) {
			this.kontenerSiatki.innerHTML = `
				<div class="col-span-full text-center text-red-600 py-8">
					<i class="fas fa-exclamation-triangle text-3xl mb-4"></i>
					<p>${wiadomosc}</p>
				</div>
			`
		}
	}
}

// Inicjalizacja po załadowaniu DOM
document.addEventListener('DOMContentLoaded', () => {
	if (document.querySelector('.portfolio-grid')) {
		new GaleriaPortfolio()
	}
})
