# Command line instructions

Git global setup

	git config --global user.name "seu nome"
	git config --global user.email "seu email"

Create a new repository

	git clone http://git.planejamento.gov.br/<nome_do_grupo>/<nome_repositorio>
	cd <nome_repositorio>
	touch README.md
	git add README.md
	git commit -m "add README"
	git push -u origin <nome_da_branch>

Existing folder or Git repository

	cd existing_folder
	git init
	git remote add origin http://git.planejamento.gov.br/<nome_do_grupo>/<nome_repositorio>
	git add .
	git commit
	git push -u origin <nome_da_branch>