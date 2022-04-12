const templateBackgroundClasses = armorial => ({
    wappenVBeerwaldeBg: armorial.wappenVBeerwaldeBg,
    wappenVSchleinitzBg: armorial.wappenVSchleinitzBg,
    wappenVSchoenbergBg: armorial.wappenVSchoenbergBg,
    wappenVSchindelBg: armorial.wappenVSchindelBg,
    wappenPflugBg: armorial.wappenPflugBg,
    wappenVitzthumVAppoldaTannrodaBg: armorial.wappenVitzthumVAppoldaTannrodaBg,
    wappenVCarlowitzBg: armorial.wappenVCarlowitzBg,
    wappenMilckauBg: armorial.wappenMilckauBg,
})
const templateForegroundClasses = armorial => ({
    wappenVBeerwaldeFg: armorial.wappenVBeerwaldeFg,
    wappenVSchleinitzFg: armorial.wappenVSchleinitzFg,
    wappenVSchoenbergFg: armorial.wappenVSchoenbergFg,
    wappenVSchindelFg: armorial.wappenVSchindelFg,
    wappenPflugFg: armorial.wappenPflugFg,
    wappenVitzthumVAppoldaTannrodaFg: armorial.wappenVitzthumVAppoldaTannrodaFg,
    wappenVCarlowitzFg: armorial.wappenVCarlowitzFg,
    wappenMilckauFg: armorial.wappenMilckauFg
})

const swapPredecessor = (dragArmorial, dropArmorial, property) => {
    [dragArmorial[property], dropArmorial[property]] = [dropArmorial[property], dragArmorial[property]]
}

export {templateBackgroundClasses, templateForegroundClasses, swapPredecessor}
