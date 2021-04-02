<template>
  <div class="mt-8 relative">
    <div class="py-3 flex items-center border-b border-50">
      <div class="px-3">
        Invoices
      </div>
    </div>

    <div class="overflow-hidden overflow-x-auto relative">
      <table
        cellpadding="0"
        cellspacing="0"
        data-testid="resource-table"
        class="table w-full">
        <thead>
          <tr>
            <th
              v-for="item in columns"
              :key="item.label"
              class="text-left">
              <span class="cursor-pointer inline-flex items-center">
                {{ item.label }}
              </span>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="invoice in invoices"
            :key="invoice.id">
            <td>
              <span class="whitespace-no-wrap text-left">
                {{ invoice.charge_id || '-' }}
              </span>
            </td>
            <td>
              <span class="whitespace-no-wrap text-left">
                {{ invoice.total / 100 }} {{ invoice.currency }}
              </span>
            </td>
            <td>
              <div class="text-center" v-if="invoice.status === 'paid'">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" aria-labelledby="check-circle" role="presentation" class="fill-current text-success"><path d="M12 22a10 10 0 1 1 0-20 10 10 0 0 1 0 20zm0-2a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm-2.3-8.7l1.3 1.29 3.3-3.3a1 1 0 0 1 1.4 1.42l-4 4a1 1 0 0 1-1.4 0l-2-2a1 1 0 0 1 1.4-1.42z"></path></svg>
              </div>
              <div class="text-center" v-else>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" aria-labelledby="x-circle" role="presentation" class="fill-current text-danger"><path d="M4.93 19.07A10 10 0 1 1 19.07 4.93 10 10 0 0 1 4.93 19.07zm1.41-1.41A8 8 0 1 0 17.66 6.34 8 8 0 0 0 6.34 17.66zM13.41 12l1.42 1.41a1 1 0 1 1-1.42 1.42L12 13.4l-1.41 1.42a1 1 0 1 1-1.42-1.42L10.6 12l-1.42-1.41a1 1 0 1 1 1.42-1.42L12 10.6l1.41-1.42a1 1 0 1 1 1.42 1.42L13.4 12z"></path></svg>
              </div>
            </td>
            <td>
              <span class="whitespace-no-wrap text-left">
                {{ invoice.period_start }}
              </span>
            </td>
            <td>
              <span class="whitespace-no-wrap text-left">
                {{ invoice.period_end }}
              </span>
            </td>
            <td class="text-right">
              <a
                v-if="invoice.link"
                :href="invoice.link"
                class="btn btn-outline text-sm"
                target="_blank"
              >
                View
              </a>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
export default {
  name: 'InvoicesTable',

  props: {
    invoices: {
      type: Array,
      default: () => [],
    },
  },

  data: () => ({
    columns: [
      { label: 'Charge ID' },
      { label: 'Amount' },
      { label: 'Status' },
      { label: 'From' },
      { label: 'To' },
      { label: 'Actions' },
    ],
  }),
};
</script>

<style scoped></style>
