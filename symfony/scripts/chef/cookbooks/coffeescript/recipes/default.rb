execute 'installCoffeescript' do
  command <<-eos
    sudo npm install coffee-script
  eos
end