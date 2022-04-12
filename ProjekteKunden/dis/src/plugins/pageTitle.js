const PageTitlePlugin = {
  install: function (Vue, options) {
    Vue.prototype.$setTitle = function (title, filterString) {
      if (!filterString) {
        document.title = `${title} - mDIS`
      } else {
        document.title = `${title} | ${filterString}  - mDIS`
      }
    }
  }
}

export default PageTitlePlugin
