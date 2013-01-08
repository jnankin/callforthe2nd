name "db"
description "Postgresql server"
run_list(
  "recipe[openssl]",
  "recipe[postgresql::server]"
)
override_attributes(
  :authorization => {
    :sudo => {
      :users => ["ubuntu"],
      :passwordless => true
    }
  }
)
