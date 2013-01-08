pkg = value_for_platform(
    [ "centos", "redhat", "fedora" ] => {"default" => "php53-pgsql"}, 
    "default" => "php5-imagick"
  )

package pkg do
  action :install
end
