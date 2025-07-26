<html>

<head>

</head>

<body>

    <div id="app">


        <input type="text" id="entrada" value="Teste">


        <input type="text" id="saida" value="Teste">


    </div>


    <div>
        <button data-luma-model="teste">Oi</button>
    </div>



    <div data-luma-if="test">
        Avava
    </div>
    <script>
        const deps = {};

        // Proxy reativo
        const state = new Proxy({}, {
            get(target, prop) {
                console.log(`GET: ${prop}`);
                return target[prop];
            },
            set(target, prop, value) {
                console.log(`SET: ${prop} = ${value}`);
                target[prop] = value;

                if (deps[prop]) {
                    deps[prop].forEach(fn => fn(value));
                }

                return true;
            },
            deleteProperty(target, prop) {
                console.log(`DELETE: ${prop}`);
                return delete target[prop];
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            // Associa DOM ↔ state
            const input = document.getElementById("entrada");
            const saida = document.getElementById("saida");

            // Observador da propriedade "nome"
            deps['nome'] = [
                val => saida.textContent = val
            ];

            // Atualiza state quando digita
            input.addEventListener('input', e => {
                state.nome = e.target.value;
            });

            // Valor inicial (só pra testar)
            state.nome = 'João';
        });
    </script>


</body>

</html>