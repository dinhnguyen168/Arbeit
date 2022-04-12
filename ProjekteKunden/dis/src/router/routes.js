import generatedFormRoutes from './generatedRoutes'
import Dashboard from '../pages/Dashboard'
import Settings from '../pages/Settings'
import Login from '../pages/Login'
import ResetPassword from '../pages/ResetPassword'
import TemplatesManager from '../pages/TemplatesManager'
import NewDataModelTemplate from '../components/templates-manager/data-models/NewDataModelTemplate'
import UpdateDataModelTemplate from '../components/templates-manager/data-models/UpdateDataModelTemplate'
import ModelForms from '../components/templates-manager/forms/ModelForms'
import NewFormTemplate from '../components/templates-manager/forms/NewFormTemplate'
import UpdateFormTemplate from '../components/templates-manager/forms/UpdateFormTemplate'
import NotFound from '../pages/NotFound'
import AccountSettings from '../pages/AccountSettings'
const DisAppForms = () => import('../components/DisAppForms')
const DisSmartForm = () => import('../components/DisSmartForm')
const ArchiveFileUpload = () => import('../components/ArchiveFileUpload')

const routes = [
  // { path: '/', redirect: '/dashboard' },
  {
    path: '/',
    component: { render: h => h('router-view') },
    meta: { requiresAuth: true, breadcrumb: 'Dashboard' },
    children: [
      {
        path: 'files-upload',
        meta: { requiredAuth: true, breadcrumb: 'Upload Files' },
        component: ArchiveFileUpload
      },
      {
        path: 'forms',
        meta: { requiresAuth: true, breadcrumb: 'Forms', breadcrumbDisabled: true },
        component: { render: h => h('router-view') },
        children: [
          {
            path: '',
            component: DisAppForms
          },
          ...generatedFormRoutes,
          {
            path: ':formName-form/:id?',
            component: DisSmartForm,
            meta: { requiresAuth: true, breadcrumb: routerParams => `${routerParams.formName}` }
          }
        ]
      },
      {
        path: '',
        component: Dashboard
      },
      {
        path: 'settings',
        component: { render: h => h('router-view') },
        meta: { requiresAuth: true, breadcrumb: 'Settings' },
        children: [
          { path: '', name: 'settings', component: Settings },
          {
            path: 'templates-manager',
            component: { render: h => h('router-view') }, // TemplatesManager,
            meta: { breadcrumb: 'Templates Manager' },
            children: [
              {
                path: '',
                component: TemplatesManager,
                name: 'templates-manager',
                meta: { requiresAuth: true }
              },
              {
                path: 'forms/:modelFullName',
                component: { render: h => h('router-view') }, // ModelForms,
                meta: { requiresAuth: true, breadcrumb: routerParams => `${routerParams.modelFullName}'s Forms` },
                children: [
                  {
                    path: '',
                    name: 'model-forms',
                    component: ModelForms
                  },
                  {
                    path: 'new', component: NewFormTemplate, name: 'new-model-form-template', meta: { requiresAuth: true, breadcrumb: routerParams => `New ${routerParams.modelFullName}'s Form` }
                  },
                  {
                    path: 'update/:formName', component: UpdateFormTemplate, name: 'update-model-form-template', meta: { requiresAuth: true, breadcrumb: routerParams => `Edit ${routerParams.formName}'s Template` }
                  }
                ]
              },
              {
                path: 'data-models/new/:moduleName', component: NewDataModelTemplate, name: 'new-data-model-template', meta: { requiresAuth: true, breadcrumb: routerParams => `Create Model in ${routerParams.moduleName}` }
              },
              {
                path: 'data-models/update/:modelFullName', component: UpdateDataModelTemplate, name: 'update-data-model-template', meta: { requiresAuth: true, breadcrumb: routerParams => `Edit ${routerParams.modelFullName}'s Template` }
              }
            ]
          },
          {
            path: 'account',
            component: { render: h => h('router-view') }, // TemplatesManager,
            meta: { breadcrumb: 'Account Settings' },
            children: [
              {
                path: '',
                component: AccountSettings,
                name: 'account-settings',
                meta: { requiresAuth: true }
              }
            ]
          }
        ]
      }
    ]
  },
  {
    path: '/login', component: Login, name: 'login'
  },
  {
    path: '/reset-password/:userId/:code', component: ResetPassword, meta: { requiresAuth: false }
  },
  {
    path: '*', component: NotFound, meta: { requiresAuth: true, breadcrumb: '' }
  }
]

export default routes
