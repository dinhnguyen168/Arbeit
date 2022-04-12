const settings = {
  numberCategories: {
    name: 'Maximale Anzahl Kategorien',
    value: 5,
    min: 3,
    max: 6,
    step: 1
  },
  questionTimeout: {
    name: 'Bedenkzeit (in Sekunden)',
    value: 20,
    min: 5,
    max: 60,
    step: 5
  },
  jokerPercentage: {
    name: 'Häufigkeit von Jokern (in Prozent)',
    value: 10, // Prozentsatz der Fragen, die Joker-Fragen sein sollen
    min: 0,
    max: 30,
    step: 5
  },
  riskPercentage: {
    name: 'Häufigkeit von Risiko-Fragen (in Prozent)',
    value: 10, // Prozentsatz der Fragen, die Risiko-Fragen sein sollen
    min: 0,
    max: 30,
    step: 5
  }
}

export { settings }
