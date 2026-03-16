from __future__ import annotations
import ast, json, re
from pathlib import Path

class SqlDumpTableParser:
    def parse_insert_rows(self, sql_path: Path, table_name: str):
        text = sql_path.read_text(encoding='utf-8')
        match = re.search(rf"INSERT INTO `{table_name}` VALUES (.*?);", text, re.S)
        if not match:
            return []
        values = match.group(1).replace('NULL', 'None')
        return list(ast.literal_eval('[' + values + ']'))

class DumpImporter:
    def __init__(self, dump_dir: Path, output_dir: Path) -> None:
        self.dump_dir = dump_dir
        self.output_dir = output_dir
        self.parser = SqlDumpTableParser()

    def write_json(self, name: str, rows, columns):
        self.output_dir.mkdir(parents=True, exist_ok=True)
        data = [{column: value for column, value in zip(columns, row)} for row in rows]
        (self.output_dir / f"{name}.json").write_text(json.dumps(data, indent=2, ensure_ascii=False), encoding='utf-8')

    def run(self) -> None:
        mappings = {
            'companies': ('project_web_companies.sql', 'companies', ['id','name','city','street','number','number_additional','zip_code','country_code','email']),
            'contacts': ('project_web_relations.sql', 'relations', ['id','name','first_name','initials','prefix','company_id','function','title','email','phone']),
            'subscription_types': ('project_web_subscription_types.sql', 'subscription_types', ['id','name','description','nr_stations','frequency_in_hours','frequency_in_days','continuous','price_per_station','valid_through']),
            'subscriptions': ('project_web_subscriptions.sql', 'subscriptions', ['id','company_id','type_id','start_date','end_date','price','notes','identifier','token']),
            'subscription_station': ('project_web_subscription_station.sql', 'subscription_station', ['subscription_id','station']),
            'countries': ('project_web_country.sql', 'country', ['country_code','country_name']),
            'endpoint_activity': ('project_web_endpoint_activity.sql', 'endpoint_activity', ['id','identifier','endpoint_used','files_downloaded','activity_date','activity_time','authorized','data_transferred']),
        }
        for output_name, (file_name, table_name, columns) in mappings.items():
            rows = self.parser.parse_insert_rows(self.dump_dir / file_name, table_name)
            self.write_json(output_name, rows, columns)

if __name__ == '__main__':
    base = Path(__file__).resolve().parent.parent
    importer = DumpImporter(base / 'dbdump', base / 'storage' / 'data')
    importer.run()
    print('Import klaar.')
