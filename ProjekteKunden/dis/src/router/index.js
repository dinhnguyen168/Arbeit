import VueRouter from 'vue-router'
import BackendService from '../services/BackendService'

import routes from './routes'
// import generatedRoutes from './generatedRoutes'

let router = new VueRouter({
  mode: 'hash',
  routes: [
    ...routes
  ]
})

router.beforeEach((to, from, next) => {
  let backendService = new BackendService()
  if (to.matched.some(record => record.meta.requiresAuth) && !backendService.isLoggedIn()) {
    next({ path: '/login', query: { redirect: to.fullPath } })
  } else {
    next()
  }
})

export default router
