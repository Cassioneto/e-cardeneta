e-cardeneta

# descrição geral

o e-cardeneta é um sistema de gestão de comunicação com os pais de uma creche infantil com crianças dos 0 ao 6 anos. O sistema deve permitir o cadastro de crianças, pais, professores, turmas, atividades, eventos, refeições, vacinas, medicamentos, alergias, restrições alimentares, observações, mensagens, avisos, comunicados, tarefas, lembretes, eventos, refeições, vacinas, medicamentos, alergias, restrições alimentares, observações, mensagens, avisos, comunicados, tarefas, lembretes.

# funcionalidades

- cadastro de crianças
- cadastro de pais
- cadastro de professores
- cadastro de turmas
- cadastro de atividades
- cadastro de eventos
- cadastro de refeições
    deve apresentar as crianças que têm restrições alimentares ou alergias.
    deve permitir a planificação semanal das refeições.
    deve permitir o cadastro de refeições por dia da semana.
    deve permitir o cadastro de refeições por horário.
    deve permitir o cadastro de refeições por tipo.
    deve permitir o cadastro de refeições por tipo.
- cadastro de vacinas
- cadastro de medicamentos
    os pais devem informar, mas também deve ser possível o cadastro pelo professor.
- cadastro de alergias
    os pais devem informar as alergias da criança
- cadastro de restrições alimentares
    os pais devem informar as restrições alimentares da criança
- cadastro de observações
    os pais devem informar as observações da criança
- cadastro de mensagens
- cadastro de avisos
    os avisos devem ser enviados para os pais via whatsapp, e podem ser gerais ou específicos para cada turma ou pais
- cadastro de comunicados
    os comunicados devem ser enviados para os pais via whatsapp, e podem ser gerais ou específicos para cada turma
- cadastro de tarefas
   as tarefas podem ser obrigatorias ou opcionais.
- cadastro de lembretes
    os lembretes devem ser enviados para os pais via whatsapp, e podem ser gerais ou específicos para cada turma ou pais
- cadastro de eventos
    os eventos devem ser enviados para os pais via whatsapp, e podem ser gerais ou específicos para cada turma ou pais
- cadastro de refeições
    as refeições devem ser enviadas para os pais via whatsapp, e podem ser gerais ou específicos para cada turma ou pais
- cadastro de vacinas
    as vacinas devem ser enviadas para os pais via whatsapp, e podem ser gerais ou específicos para cada turma ou pais
- cadastro de medicamentos
    os pais devem informar os medicamentos da criança, bem com a dosagem e o horário que deve ser administrado. deve ter um campo para informar se o medicamento foi administrado ou não. e deve informar via whatsapp para os pais se o medicamento foi administrado ou não. e deve alertar via whatsapp para a educadora de infancia( professora) 2 minutos antes do horario de administracao do medicamento via whatsapp.
- cadastro de alergias
    
- cadastro de restrições alimentares
- cadastro de observações
- cadastro de mensagens
 deve ser enviado para os pais via whatsapp, e podem ser gerais ou específicos para cada turma ou pais
 e deve permitir que os pais enviem mensagens para os professores.
 e deve permitir que os pais respondam as mensagens.

- cadastro de avisos
- cadastro de comunicados
- cadastro de tarefas
- cadastro de lembretes
    deve ser apenas um lembrete para o professor de infancia(educadora) de infancia(professora), não sendo visivel para os pais.
- cadastro de administradores do sistema
- cadastro de responsáveis legais pelas crianças.
 caso a criança tenha babá, deve ser cadastrada como responsável legal. e deve ter acesso apenas ao que diz respeito a criança. como avisos, comunicados, tarefas, lembretes, eventos, refeições, vacinas, medicamentos, alergias, restrições alimentares, observações, mensagens.
-logo e nome da instituição customizável
-dev usar o https://wwebjs.dev/ para notificações via whatsapp

especial atenção para a segurança dos dados das crianças e pais. os dados devem ser criptografados em repouso e em trânsito. os dados devem ser armazenados em conformidade com a lgpd.
# stack tecnologica,

deve ser desenvolvido em php sem framework, com banco de dados mysql, frontend em html, css( bootstrap 5) e javascript puro, sem uso de bibliotecas ou frameworks, exceto as mencionadas acima.

# banco de dados

 mysql, banco de dados relacional, com tabelas para cada funcionalidade, com relacionamentos entre as tabelas. deve seguir o padrão de normalização de banco de dados.

# frontend

html, css( bootstrap 5) e javascript, o uso de bibliotecas ou frameworks apenas se ajudar na experiência do usuário. deve ser responsivo. deve ser acessivel. deve ser rapido. e suportar ajax e validação de formulários no lado do cliente.

# backend

deve ser desenvolvido em php sem framework,
para comunicar com o whatsapp deve usar a api do whatsapp  https://wwebjs.dev/ npm install whatsapp-web.js

# segurança

os dados devem ser criptografados em repouso e em trânsito. os dados devem ser armazenados em conformidade com a lgpd. deve verificar o owasp top 10 e implementar as medidas de segurança necessárias.

## fix0.1

# funcionalidades

- cadastro de administradores do sistema
 deve permitir o cadastro de administradores do sistema, com diferentes niveis de acesso. como super admin, admin, professor, responsavel legal, babá.
 deve permitir gerar relatorios
 o dashboard deve apresentar os dados de forma clara e objetiva.
 deve permitir o cadastro de administradores do sistema, com diferentes niveis de acesso. como super admin, admin, professor, responsavel legal, babá.
 deve permitir gerar relatorios
 o dashboard deve apresentar os dados de forma clara e objetiva. deve permitir as gestão das turmas, crianças, pais, professores, atividades, eventos, refeições, vacinas, medicamentos, alergias, restrições alimentares, observações, mensagens, avisos, comunicados, tarefas, lembretes, eventos, refeições, vacinas, medicamentos, alergias, restrições alimentares, observações, mensagens, avisos, comunicados, tarefas, lembretes. de permitir a configuração de cores e logo da instituição.
 deve permitir a configuração de cores e logo da instituição. deve permitir a configuração da integração com o whatsapp. deve permitir a configuração de cores e logo da instituição. deve permitir a configuração da integração com o whatsapp.

