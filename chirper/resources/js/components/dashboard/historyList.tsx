import { useHistory } from '@/hooks/useHistory';

export function HistoryTest() {
    const {
        historicos,
        isLoading,
        error
    } = useHistory(2);

    if (isLoading) {
        return <h1>Carregando histórico...</h1>;
    }

    if (error) {
        return <h1>Erro: {error}</h1>;
    }

    return (
        <div>
            <h1>Histórico do chamado 2</h1>

            {historicos.length === 0 ? (
                <p>Nenhum histórico encontrado.</p>
            ) : (
                historicos.map((historico, index) => (
                    <div key={index}>
                        <p>Data: {historico.data}</p>
                        <p>Descrição: {historico.descricao}</p>
                        <p>Chamado: {historico.id_chamado}</p>
                        <p>Técnico: {historico.id_usuario_tecnico}</p>
                        <hr />
                    </div>
                ))
            )}
        </div>
    );
}