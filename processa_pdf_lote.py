import sys
import os
from PyPDF2 import PdfReader, PdfWriter

if len(sys.argv) != 3:
    print("Uso: python extrair_paginas_pdf.py <caminho_pdf> <diretorio_destino>")
    sys.exit(1)

caminho_pdf = sys.argv[1]
diretorio_destino = sys.argv[2]

# Garante que o diretório de destino existe
os.makedirs(diretorio_destino, exist_ok=True)

try:
    reader = PdfReader(caminho_pdf)
except FileNotFoundError:
    print(f"Arquivo {caminho_pdf} não encontrado.")
    sys.exit(1)
except Exception as e:
    print(f"Erro ao abrir o PDF: {e}")
    sys.exit(1)

for i, pagina in enumerate(reader.pages):
    writer = PdfWriter()
    writer.add_page(pagina)
    saida = os.path.join(diretorio_destino, f"pagina_{i+1}.pdf")
    with open(saida, "wb") as f:
        writer.write(f)
    print(f"Página {i+1} salva em {saida}")

print("Extração concluída com sucesso.")