import re,sys
root=sys.argv[1]
p=root+'/FullTimeAPI/Models/Fixture.cs'; s=open(p,encoding='utf-8').read()
assert 'public string Competition { get; set; }' in s
if 'Status' not in s:
    s=s.replace('public string Competition { get; set; }','public string Competition { get; set; }\n        public string Status { get; set; }'); open(p,'w',encoding='utf-8').write(s)
p=root+'/FullTimeAPI/Services/FixturesService.cs'; s=open(p,encoding='utf-8').read()
assert s.count('return new Fixture')==1
if 'status-notes' not in s:
    s=s.replace('return new Fixture','var statusNode = item.SelectSingleNode(".//td[contains(@class, \'status-notes\')]");\n                var status = statusNode != null ? Helpers.NormalizeText(statusNode.InnerText) : string.Empty;\n\n                return new Fixture',1)
    s,n=re.subn(r'Competition = competition\s*\};','Competition = competition,\n                    Status = status\n                };',s,count=1); assert n==1
    open(p,'w',encoding='utf-8').write(s)
print('patched')
