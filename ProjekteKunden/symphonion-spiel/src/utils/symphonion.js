class Plate {
    constructor(id, name) {
        this.id = id
        this.name = name
        this.radius = 30
        this.isTakenBy = null
    }

    checkInside({ clientX, clientY }, app) {
        const box = app.$el.querySelector('#' + this.id).getBoundingClientRect()
        const clickMargin = box.width / 6
        return box.left < clientX && box.right - clickMargin > clientX && box.top < clientY && box.bottom > clientY;
    }
}

export { Plate }
