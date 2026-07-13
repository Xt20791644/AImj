import api from './index'

export function getBalance() {
  return api.get('/credits/balance')
}

export function getTransactions(page = 1) {
  return api.get('/credits/transactions', { params: { page } })
}

export function recharge(amount) {
  return api.post('/credits/recharge', { amount })
}
