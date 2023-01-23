document.addEventListener('DOMContentLoaded', () => {
  if (window.location.toString().includes("bypost_shipping_method")) {
    document.querySelector('.form-table').classList.add('bypost-settings')
    // Listen for weight-based prices
    let weightPricesElements = getWeightPricesElements()
    const weightToggle = document.querySelector('#woocommerce_bypost_shipping_method_weight_based_shipping')
    const pickupPrice = document.querySelector('#woocommerce_bypost_shipping_method_pickup_point').closest('tr')
    const doorPrice = document.querySelector('#woocommerce_bypost_shipping_method_door_delivery').closest('tr')

    // Initial setup:
    checkCheckbox(weightToggle)
    setHeadingClasses()

    // Listener:
    weightToggle.addEventListener('change', (ev) => {
      checkCheckbox(weightToggle)
    })

    function checkCheckbox() {
      if (weightToggle.checked) {
        pickupPrice.classList.add('hidden')
        doorPrice.classList.add('hidden')
        weightPricesElements.forEach(element => {
          element.closest('tr').classList.remove('hidden')
        })
      } else {
        pickupPrice.classList.remove('hidden')
        doorPrice.classList.remove('hidden')
        weightPricesElements.forEach(element => {
          element.closest('tr').classList.add('hidden')
        })
      }
    }

    function getWeightPricesElements() {
      return document.querySelectorAll('.bypost-settings .weight-class')
    }

    function setHeadingClasses() {
      let groups = document.querySelectorAll('.group-start')
      groups.forEach(group => {
        group.closest('tr').classList.add('group-heading')
        group.closest('tr').classList.add(group.classList.item(group.classList.length - 1))
      })
    }

  }

})