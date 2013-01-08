name "webapi"
description "A web and API server"
run_list(
  "role[base]",
  "recipe[php5-fpm]",
  "recipe[nginx]"
)

override_attributes(
  :authorization => {
    :sudo => {
      :users => ["ubuntu"],
      :passwordless => true
    }
  }
)