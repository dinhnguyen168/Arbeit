const startArmorials = [
    {name: 'Wappen_v_Beerwalde', predecessorBG: 'Wappen_Pflug', predecessorFG: 'Wappen_v_Schleinitz'},
    {name: 'Wappen_v_Schleinitz', predecessorBG: 'Wappen_v_Beerwalde', predecessorFG: 'Wappen_Milckau'},
    {name: 'Wappen_v_Schoenberg', predecessorBG: 'Wappen_Vitzthum_v_Appolda_Tannroda', predecessorFG: 'Wappen_v_Beerwalde'},
    {name: 'Wappen_v_Schindel', predecessorBG: 'Wappen_v_Schleinitz', predecessorFG: 'Wappen_Pflug'},
    {name:  'Wappen_Pflug', predecessorBG: 'Wappen_Milckau', predecessorFG: 'Wappen_v_Carlowitz'},
    {name: 'Wappen_Vitzthum_v_Appolda_Tannroda', predecessorBG: 'Wappen_v_Carlowitz', predecessorFG: 'Wappen_v_Schoenberg'},
    {name: 'Wappen_v_Carlowitz', predecessorBG: 'Wappen_v_Schoenberg', predecessorFG: 'Wappen_Vitzthum_v_Appolda_Tannroda'},
    {name: 'Wappen_Milckau', predecessorBG: 'Wappen_v_Schindel', predecessorFG: 'Wappen_v_Schindel'},
]

class Armorial {
    constructor(name, background, foreground, predecessorBG, predecessorFG) {
        this.name = name
        this.background = background
        this.foreground = foreground
        this.predecessorBG = predecessorBG
        this.predecessorFG = predecessorFG
    }

    checkInside({clientX, clientY}, app) {
        const box = app.$el.querySelector(`#${this.name}`).getBoundingClientRect()
        const clickMargin = box.width / 6
        return box.left < clientX && box.right - clickMargin > clientX && box.top < clientY && box.bottom > clientY;
    }
}

export { Armorial, startArmorials }
