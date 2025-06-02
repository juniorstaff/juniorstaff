from flask import Flask, request, render_template

app = Flask(__name__)

@app.route('/')
def form():
    return render_template('form.html')

@app.route('/submit_form', methods=['POST'])
def submit_form():
    form_data = request.form
    id = form_data['id']
    mac = form_data['mac']
    equipamento_hfc = form_data['equipamento_hfc']
    descricao = form_data['descricao']
    tecn_origem = form_data['tecn_origem']
    data_de_recebimento = form_data['data_de_recebimento']
    nota_fiscal = form_data['nota_fiscal']
    rma = form_data['rma']
    
    # Handle the form data (e.g., save to database, process, etc.)
    # For this example, we'll just print it to the console
    print(f"ID: {id}")
    print(f"MAC: {mac}")
    print(f"Equipamento HFC: {equipamento_hfc}")
    print(f"Descrição: {descricao}")
    print(f"Tecn Origem: {tecn_origem}")
    print(f"Data de Recebimento: {data_de_recebimento}")
    print(f"Nota Fiscal: {nota_fiscal}")
    print(f"RMA: {rma}")
    
    return "Form submitted successfully!"

if __name__ == '__main__':
    app.run(debug=True)