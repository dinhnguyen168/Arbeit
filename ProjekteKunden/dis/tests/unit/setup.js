import Vue from 'vue'
import Vuetify from 'vuetify'
import MousetrapPlugin from '@/plugins/mousetrap'

import DisBreadcrumbs from '@/components/DisBreadcrumbs'
import DisDataTable from '@/components/DisDataTable'
import DisFileUpload from '@/components/DisFileUpload'
import DisFilterForm from '@/components/DisFilterForm'
import DisForm from '@/components/DisForm'
import DisListValuesForm from '@/components/DisListValuesForm'
import DisLoginDialog from '@/components/DisLoginDialog'
import DisQrCodeScanner from '@/components/DisQrCodeScanner'
import DisServerValidationErrorsView from '@/components/DisServerValidationErrorsView'
import DisSimplePanel from '@/components/DisSimplePanel'
import DisSmartForm from '@/components/DisSmartForm'
import DisAutoIncrementInput from '@/components/input/DisAutoIncrementInput'
import DisDateInput from '@/components/input/DisDateInput'
import DisDateTimeInput from '@/components/input/DisDateTimeInput'
import DisSelectInput from '@/components/input/DisSelectInput'
import DisSwitchInput from '@/components/input/DisSwitchInput'
import DisTextareaInput from '@/components/input/DisTextareaInput'
import DisTextInput from '@/components/input/DisTextInput'
import DisTimeInput from '@/components/input/DisTimeInput'

Vue.use(MousetrapPlugin)

jest.mock('@/services/ListValuesService')
jest.mock('@/services/FormService')
jest.mock('@/services/CrudService')

Vue.use(Vuetify)
Vue.component('DisAutoIncrementInput', DisAutoIncrementInput)
Vue.component('DisBreadcrumbs', DisBreadcrumbs)
Vue.component('DisDataTable', DisDataTable)
Vue.component('DisFileUpload', DisFileUpload)
Vue.component('DisFilterForm', DisFilterForm)
Vue.component('DisForm', DisForm)
Vue.component('DisListValuesForm', DisListValuesForm)
Vue.component('DisLoginDialog', DisLoginDialog)
Vue.component('DisQrCodeScanner', DisQrCodeScanner)
Vue.component('DisServerValidationErrorsView', DisServerValidationErrorsView)
Vue.component('DisSimplePanel', DisSimplePanel)
Vue.component('DisSmartForm', DisSmartForm)
Vue.component('DisDateInput', DisDateInput)
Vue.component('DisDateTimeInput', DisDateTimeInput)
Vue.component('DisSelectInput', DisSelectInput)
Vue.component('DisSwitchInput', DisSwitchInput)
Vue.component('DisTextareaInput', DisTextareaInput)
Vue.component('DisTextInput', DisTextInput)
Vue.component('DisTimeInput', DisTimeInput)
