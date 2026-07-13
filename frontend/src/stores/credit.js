import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../api'

export const useCreditStore = defineStore('credit', () => {
  const balance = ref(0)
  const transactions = ref([])
  const loading = ref(false)

  async function fetchBalance() {
    const result = await api.get('/credits/balance')
    balance.value = result.balance
    return result
  }

  async function fetchTransactions(page = 1) {
    loading.value = true
    try {
      const result = await api.get('/credits/transactions', { params: { page } })
      transactions.value = result.data
      return result
    } finally {
      loading.value = false
    }
  }

  async function recharge(amount) {
    const result = await api.post('/credits/recharge', { amount })
    return result
  }

  return { balance, transactions, loading, fetchBalance, fetchTransactions, recharge }
})
