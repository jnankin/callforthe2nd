name "vagrant"
description "Vagrant development box"
run_list(
  "recipe[php::module_xdebug]",
  "recipe[localtunnel]"
)
override_attributes(
  :authorization => {
    :sudo => {
      :users => ["ubuntu"],
      :passwordless => true
    }
  }
)
