import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../api'

export const useCreditStore = defineStore('credit', () => {
  const balance = ref(0)
  const transactions = ref([])
  const loading = ref(false)

  async function fetchBalance() {
    const { data } = await api.get('/credits/balance')
    balance.value = data.balance
    return data
  }

  async function fetchTransactions(page = 1) {
    loading.value = true
    try {
      const { data } = await api.get('/credits/transactions', { params: { page } })
      transactions.value = data.data
      return data
    } finally {
      loading.value = false
    }
  }

  async function recharge(amount) {
    const { data } = await api.post('/credits/recharge', { amount })
    return data
  }

  return { balance, transactions, loading, fetchBalance, fetchTransactions, recharge }
})
