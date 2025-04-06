-- Tabela de conexão WhatsApp
CREATE TABLE IF NOT EXISTS conexao (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  instance_name TEXT UNIQUE NOT NULL,
  number TEXT UNIQUE NOT NULL,
  status TEXT NOT NULL DEFAULT 'pendente',
  criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de contatos
CREATE TABLE IF NOT EXISTS contatos (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  telefone TEXT NOT NULL,
  nome TEXT,
  email TEXT,
  status TEXT,
  etiqueta TEXT,
  mensagem TEXT,
  informacao TEXT,
  retorno TEXT,
  data TEXT,
  prioridade TEXT,
  variavelA TEXT,
  variavelB TEXT,
  variavelC TEXT,
  data_alteracao TEXT,
  data_criacao TEXT DEFAULT CURRENT_TIMESTAMP,
  ultimo_envio TEXT,
  grupoA TEXT,
  grupoB TEXT,
  grupoC TEXT,
  notifica TEXT,
  arquivo_extra TEXT,
  gpt BOOLEAN,  
  ultima_mensagem TEXT
);

CREATE TABLE IF NOT EXISTS leads (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  telefone TEXT NOT NULL,
  nome TEXT,
  email TEXT,
  status TEXT,
  etiqueta TEXT,
  data_envio TEXT,
  data_contato TEXT,
  data_resposta TEXT,
  data_alteracao TEXT,
  grupoB TEXT,
  data_criacao TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS etapas_crm (id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT NOT NULL);

CREATE TABLE IF NOT EXISTS campanhas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT,
    tabela_fila TEXT,
    filtros TEXT,
    origem TEXT,
    mensagem TEXT,
    criada_em TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS etapas_crm (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL
);
